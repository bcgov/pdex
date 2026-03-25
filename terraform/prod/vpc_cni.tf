resource "kubernetes_manifest" "eni_config_a" {
  depends_on = [
    aws_eks_addon.vpc-cni-addon
  ]

  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata   = { name = "ca-central-1a" }
    spec = {
      subnet         = local.pod_subnet_by_az["ca-central-1a"]
      securityGroups = [data.aws_security_group.eks_node_sg.id]
    }
  }
}

resource "kubernetes_manifest" "eni_config_b" {
  depends_on = [
    aws_eks_addon.vpc-cni-addon
  ]

  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata   = { name = "ca-central-1b" }
    spec = {
      subnet         = local.pod_subnet_by_az["ca-central-1b"]
      securityGroups = [data.aws_security_group.eks_node_sg.id]
    }
  }
}