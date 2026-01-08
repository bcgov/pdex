resource "helm_release" "secrets_csi_driver" {
  name = "secrets-store-csi-driver"

  repository = "https://kubernetes-sigs.github.io/secrets-store-csi-driver/charts"
  chart      = "secrets-store-csi-driver"
  namespace  = "kube-system"
  version    = "1.4.3"

  # MUST be set if you use ENV variables
  set = [{
    name  = "syncSecret.enabled"
    value = true
  }]

  depends_on = [aws_eks_addon.aws-efs-csi-driver]
}

resource "helm_release" "secrets_csi_driver_aws_provider" {
  name = "secrets-store-csi-driver-provider-aws"

  repository = "https://aws.github.io/secrets-store-csi-driver-provider-aws"
  chart      = "secrets-store-csi-driver-provider-aws"
  namespace  = "kube-system"
  version    = "0.3.8"

  depends_on = [helm_release.secrets_csi_driver]
}

# Pod Identity role for Secrets Manager access
resource "aws_iam_role" "pdex_secrets" {
  name = "${aws_eks_cluster.pdex-cluster.name}-pdex-secrets"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = [
        "sts:AssumeRole",
        "sts:TagSession"
      ]
      Effect = "Allow"
      Principal = {
        Service = "pods.eks.amazonaws.com"
      }
    }]
  })
}

resource "aws_iam_policy" "pdex_secrets" {
  name = "${aws_eks_cluster.pdex-cluster.name}-pdex-secrets"
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "secretsmanager:GetSecretValue",
          "secretsmanager:DescribeSecret"
        ]
        Resource = "arn:aws:secretsmanager:ca-central-1:814738839437:secret:secrets.env-bV4LPz"
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "pdex_secrets" {
  policy_arn = aws_iam_policy.pdex_secrets.arn
  role       = aws_iam_role.pdex_secrets.name
}

resource "aws_eks_pod_identity_association" "pdex_secrets" {
  cluster_name    = aws_eks_cluster.pdex-cluster.name
  namespace       = "default"
  service_account = "pdex-secret-manager-sa"
  role_arn        = aws_iam_role.pdex_secrets.arn
  
  depends_on = [
    aws_eks_addon.pod-identity-addon,
    aws_iam_role_policy_attachment.pdex_secrets
  ]
}

output "pdex_secrets_role_arn" {
  value = aws_iam_role.pdex_secrets.arn
}