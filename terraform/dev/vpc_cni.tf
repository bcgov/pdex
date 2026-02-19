data "aws_subnets" "pod_subnets" {
  filter {
    name   = "tag:Name"
    values = ["BCGOV-LZA-extended-app-ca-central-1a", "BCGOV-LZA-extended-app-ca-central-1b"]
  }
}

# “Which subnet + security group should I use to create the pod ENI for this node?”
resource "kubernetes_manifest" "eni_config_a" {
  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata   = { name = "ca-central-1a" }
    spec = {
      subnet         = data.aws_subnets.pod_subnets.ids[0]
      securityGroups = [data.aws_security_group.web.id]
    }
  }
}

resource "kubernetes_manifest" "eni_config_b" {
  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata   = { name = "ca-central-1b" }
    spec = {
      subnet         = data.aws_subnets.pod_subnets.ids[1]
      securityGroups = [data.aws_security_group.web.id]
    }
  }
}

resource "aws_eks_addon" "vpc_cni" {
  cluster_name                = aws_eks_cluster.pdex-cluster.name
  addon_name                  = "vpc-cni"
  resolve_conflicts_on_create = "OVERWRITE"
  resolve_conflicts_on_update = "OVERWRITE"

  configuration_values = jsonencode({
    env = {
      AWS_VPC_K8S_CNI_CUSTOM_NETWORK_CFG = "true"                        # Pods get IPs from ENIs that live in the ENIConfig subnets
      ENI_CONFIG_LABEL_DEF               = "topology.kubernetes.io/zone" # how to choose which ENIConfig to use.
      AWS_VPC_K8S_CNI_EXTERNALSNAT       = "true"                        # Do not do SNAT on the node.
    }
  })

  depends_on = [aws_eks_cluster.pdex-cluster]
}
