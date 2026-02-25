resource "kubernetes_manifest" "eni_config_a" {
  depends_on = [aws_eks_addon.vpc-cni-addon]

  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata   = { name = "ca-central-1a" }
    spec = {
      subnet         = data.aws_subnets.pod.ids[0]
      securityGroups = [data.aws_security_group.eks_node_sg.id]
    }
  }
}

resource "kubernetes_manifest" "eni_config_b" {
  depends_on = [aws_eks_addon.vpc-cni-addon]

  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata   = { name = "ca-central-1b" }
    spec = {
      subnet         = data.aws_subnets.pod.ids[1]
      securityGroups = [data.aws_security_group.eks_node_sg.id]
    }
  }
}