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

resource "helm_release" "aws_load_balancer_controller" {
  name       = "aws-load-balancer-controller"
  namespace  = "kube-system"
  repository = "https://aws.github.io/eks-charts"
  chart      = "aws-load-balancer-controller"
  version    = "1.7.2"
  wait       = true
  timeout    = 600

  set = [
    {
      name  = "clusterName"
      value = aws_eks_cluster.pdex-cluster.name
    },
    {
      name  = "region"
      value = var.aws_region
    },
    {
      name  = "vpcId"
      value = data.aws_vpc.main.id
    },
    {
      name  = "serviceAccount.name"
      value = "aws-load-balancer-controller"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_iam_role_policy_attachment.alb_attachment
  ]
}

