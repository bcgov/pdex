# Tag extended subnets so EKS/VPC CNI can use them for custom networking / ENIConfig
resource "aws_ec2_tag" "pod_subnet_cluster_tag" {
  for_each    = toset(data.aws_subnets.pod.ids)
  resource_id = each.value
  key         = "kubernetes.io/cluster/${aws_eks_cluster.pdex-cluster.name}"
  value       = "shared"
}

resource "aws_ec2_tag" "pod_subnet_internal_elb_tag" {
  for_each    = toset(data.aws_subnets.pod.ids)
  resource_id = each.value
  key         = "kubernetes.io/role/internal-elb"
  value       = "1"
}

resource "kubernetes_manifest" "eni_config_a" {
  depends_on = [
    aws_eks_addon.vpc-cni-addon,
    aws_ec2_tag.pod_subnet_cluster_tag,
    aws_ec2_tag.pod_subnet_internal_elb_tag,
  ]

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
  depends_on = [
    aws_eks_addon.vpc-cni-addon,
    aws_ec2_tag.pod_subnet_cluster_tag,
    aws_ec2_tag.pod_subnet_internal_elb_tag,
  ]

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