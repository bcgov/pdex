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

  depends_on = [helm_release.efs_csi_driver]
}

resource "helm_release" "secrets_csi_driver_aws_provider" {
  name = "secrets-store-csi-driver-provider-aws"

  repository = "https://aws.github.io/secrets-store-csi-driver-provider-aws"
  chart      = "secrets-store-csi-driver-provider-aws"
  namespace  = "kube-system"
  version    = "0.3.8"

  depends_on = [helm_release.secrets_csi_driver]
}

data "aws_iam_policy_document" "pdex_secrets" {
  statement {
    actions = ["sts:AssumeRoleWithWebIdentity"]
    effect  = "Allow"

    condition {
      test     = "StringEquals"
      variable = "${replace(aws_iam_openid_connect_provider.eks.url, "https://", "")}:sub"
      values   = ["system:serviceaccount:default:pdex-secret-manager-sa"]
    }

    principals {
      identifiers = [aws_iam_openid_connect_provider.eks.arn]
      type        = "Federated"
    }
  }
}

resource "aws_iam_role" "pdex_secrets" {
  name               = "${aws_eks_cluster.eks.name}-pdex-secrets"
  assume_role_policy = data.aws_iam_policy_document.pdex_secrets.json
}

resource "aws_iam_policy" "pdex_secrets" {
  name = "${aws_eks_cluster.eks.name}-pdex-secrets"
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

output "pdex_secrets_role_arn" {
  value = aws_iam_role.pdex_secrets.arn
}