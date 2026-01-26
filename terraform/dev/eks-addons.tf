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

resource "helm_release" "metrics_server" {
  name       = "metrics-server"
  namespace  = "kube-system"
  repository = "https://kubernetes-sigs.github.io/metrics-server/"
  chart      = "metrics-server"
  version    = "3.12.1" # pick a stable version; you can bump later
  wait       = true
  timeout    = 600

  # EKS commonly needs these kubelet flags to avoid metrics not available
  set = [
    {
      name  = "args[0]"
      value = "--kubelet-insecure-tls"
    },
    {
      name  = "args[1]"
      value = "--kubelet-preferred-address-types=InternalIP,ExternalIP,Hostname"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_addon.coredns-addon
  ]
}
resource "helm_release" "vpa" {
  name       = "vpa"
  namespace  = "kube-system"
  repository = "https://charts.fairwinds.com/stable"
  chart      = "vpa"
  version    = "4.5.0"
  wait       = true
  timeout    = 600

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_addon.coredns-addon
  ]
}