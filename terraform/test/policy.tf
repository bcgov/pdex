resource "aws_iam_policy" "alb_policy" {
  name   = "AWSLoadBalancerControllerIAMPolicy-Test"
  policy = file("${path.module}/iam_policy.json")
}

resource "aws_iam_role" "alb_role" {
  name = "AWSLoadBalancerControllerIAMRole-Test"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
            "Effect": "Allow",
            "Principal": {
                "Service": "pods.eks.amazonaws.com"
            },
            "Action": [
                "sts:AssumeRole",
                "sts:TagSession"
            ]
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "alb_attachment" {
  role       = aws_iam_role.alb_role.name
  policy_arn = aws_iam_policy.alb_policy.arn
}


############################################
# RDS Proxy: role used by RDS to read secrets
############################################

resource "aws_iam_policy" "pdex_rds_proxy_secrets_policy" {
  name = "pdex-cluster-pdex-secrets"

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "secretsmanager:GetSecretValue",
          "secretsmanager:DescribeSecret"
        ]
        Resource = [
          var.pdex_rds_secret_arn_wildcard,
          var.pdex_rds_envpref_secret_arn_wildcard,
          "arn:aws:secretsmanager:ca-central-1:634503648219:secret:secrets.env-bV4LPz*"
        ]
      }
    ]
  })
}

resource "aws_iam_role" "pdex_rds_proxy_secrets_role" {
  name = "${aws_eks_cluster.pdex-cluster.name}-rds-proxy-secrets"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Principal = { Service = "rds.amazonaws.com" }
        Action   = "sts:AssumeRole"
      }
    ]
  })
}

resource "aws_iam_role" "pdex_pods_secrets_role" {
  name = "${aws_eks_cluster.pdex-cluster.name}-pdex-secrets"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Principal = { Service = "pods.eks.amazonaws.com" }
        Action   = ["sts:AssumeRole", "sts:TagSession"]
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "pdex_rds_proxy_secrets_attach" {
  role       = aws_iam_role.pdex_rds_proxy_secrets_role.name
  policy_arn = aws_iam_policy.pdex_rds_proxy_secrets_policy.arn
}


resource "aws_iam_role_policy_attachment" "pdex_secrets" {
  policy_arn = aws_iam_policy.pdex_rds_proxy_secrets_policy.arn
  role       = aws_iam_role.pdex_pods_secrets_role.name
}

resource "aws_eks_pod_identity_association" "pdex_secrets" {
  cluster_name    = aws_eks_cluster.pdex-cluster.name
  namespace       = "default"
  service_account = "pdex-secret-manager-sa"
  role_arn        = aws_iam_role.pdex_pods_secrets_role.arn
  
  depends_on = [
    aws_eks_addon.pod-identity-addon,
    aws_iam_role_policy_attachment.pdex_rds_proxy_secrets_attach
  ]
}

output "pdex_secrets_role_arn" {
  value = aws_iam_role.pdex_pods_secrets_role.arn
}