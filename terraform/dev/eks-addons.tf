data "aws_eks_cluster" "this" {
  name = aws_eks_cluster.pdex-cluster.name
}

data "aws_eks_cluster_auth" "this" {
  name = aws_eks_cluster.pdex-cluster.name
}

provider "kubernetes" {
  host                   = data.aws_eks_cluster.this.endpoint
  cluster_ca_certificate = base64decode(data.aws_eks_cluster.this.certificate_authority[0].data)
  token                  = data.aws_eks_cluster_auth.this.token
}

provider "helm" {
  kubernetes = {
    host                   = data.aws_eks_cluster.this.endpoint
    cluster_ca_certificate = base64decode(data.aws_eks_cluster.this.certificate_authority[0].data)
    token                  = data.aws_eks_cluster_auth.this.token
  }
}

resource "helm_release" "secrets_store_csi_driver" {
  name       = "csi-secrets-store"
  namespace  = "kube-system"
  repository = "https://kubernetes-sigs.github.io/secrets-store-csi-driver/charts"
  chart      = "secrets-store-csi-driver"
  version    = "1.4.6"

  set = [
    {
        name  = "syncSecret.enabled"
        value = "true"
    },
    {
        name  = "enableSecretRotation"
        value = "true"
    }
  ]

  depends_on = [aws_eks_cluster.pdex-cluster]
}

# install AWS Load Balancer Controller needed for ALB ingress
resource "helm_release" "aws_load_balancer_controller" {
  name       = "aws-load-balancer-controller"
  namespace  = "kube-system"
  repository = "https://aws.github.io/eks-charts"
  chart      = "aws-load-balancer-controller"
  version    = "1.7.2"

  set {
    name  = "clusterName"
    value = aws_eks_cluster.pdex-cluster.name
  }

  set {
    name  = "region"
    value = var.aws_region
  }

  set {
    name  = "vpcId"
    value = data.aws_vpc.main.id
  }

  set {
    name  = "serviceAccount.name"
    value = "aws-load-balancer-controller"
  }

  set {
    name  = "serviceAccount.annotations.eks\\.amazonaws\\.com/role-arn"
    value = aws_iam_role.alb_role.arn
  }

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_iam_role_policy_attachment.alb_attachment
  ]
}
