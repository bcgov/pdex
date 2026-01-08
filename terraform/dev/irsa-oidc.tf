# Get the cluster's OIDC issuer thumbprint
data "tls_certificate" "eks_oidc" {
  url = aws_eks_cluster.pdex-cluster.identity[0].oidc[0].issuer
}

# Register the cluster OIDC issuer in IAM (this is what your account is missing)
resource "aws_iam_openid_connect_provider" "eks" {
  url             = aws_eks_cluster.pdex-cluster.identity[0].oidc[0].issuer
  client_id_list  = ["sts.amazonaws.com"]
  thumbprint_list = [data.tls_certificate.eks_oidc.certificates[0].sha1_fingerprint]
}
