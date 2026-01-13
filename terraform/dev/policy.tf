resource "aws_iam_policy" "alb_policy" {
  name   = "AWSLoadBalancerControllerIAMPolicy-Dev"
  policy = file("${path.module}/iam_policy.json")
}

resource "aws_iam_role" "alb_role" {
  name = "AWSLoadBalancerControllerIAMRole-Dev"

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

# (Optional but recommended) look up the secrets by name so you don't hardcode ARNs
data "aws_secretsmanager_secret" "pdex_rds_creds" {
  name = "pdex-rds-creds"
}

data "aws_secretsmanager_secret" "pdex_rds_creds_envpref" {
  name = "secrets.env-bV4LPz-pdex-rds-creds"
}

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
          data.aws_secretsmanager_secret.pdex_rds_creds.arn,
          data.aws_secretsmanager_secret.pdex_rds_creds_envpref.arn
        ]
      }
    ]
  })
}

resource "aws_iam_role" "pdex_rds_proxy_secrets_role" {
  name = "pdex-cluster-pdex-secrets"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Principal = {
          Service = "rds.amazonaws.com"
        }
        Action = "sts:AssumeRole"
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "pdex_rds_proxy_secrets_attach" {
  role       = aws_iam_role.pdex_rds_proxy_secrets_role.name
  policy_arn = aws_iam_policy.pdex_rds_proxy_secrets_policy.arn
}
