# Pod Identity role for Secrets Manager access
locals {
  oidc_hostpath = replace(aws_iam_openid_connect_provider.eks.url, "https://", "")
}

resource "aws_iam_role" "pdex_secrets" {
  name = "${aws_eks_cluster.pdex-cluster.name}-pdex-secrets"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = "sts:AssumeRoleWithWebIdentity"
      Principal = {
        Federated = aws_iam_openid_connect_provider.eks.arn
      }
      Condition = {
        StringEquals = {
          "${local.oidc_hostpath}:aud" = "sts.amazonaws.com"
          "${local.oidc_hostpath}:sub" = "system:serviceaccount:default:pdex-secret-manager-sa"
        }
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
        Resource = "arn:aws:secretsmanager:ca-central-1:814738839437:secret:secrets.env-bV4LPz*"
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "pdex_secrets" {
  policy_arn = aws_iam_policy.pdex_secrets.arn
  role       = aws_iam_role.pdex_secrets.name
}

# resource "aws_eks_pod_identity_association" "pdex_secrets" {
#   cluster_name    = aws_eks_cluster.pdex-cluster.name
#   namespace       = "default"
#   service_account = "pdex-secret-manager-sa"
#   role_arn        = aws_iam_role.pdex_secrets.arn
  
#   depends_on = [
#     aws_eks_addon.pod-identity-addon,
#     aws_iam_role_policy_attachment.pdex_secrets
#   ]
# }

output "pdex_secrets_role_arn" {
  value = aws_iam_role.pdex_secrets.arn
}