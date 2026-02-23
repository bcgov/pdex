data "aws_subnets" "pod_subnets" {
  filter {
    name   = "tag:Name"
    values = ["BCGOV-LZA-extended-app-ca-central-1a", "BCGOV-LZA-extended-app-ca-central-1b"]
  }
}

# "Which subnet + security group should I use to create the pod ENI for this node?"
resource "kubernetes_manifest" "eni_config_a" {

  depends_on = [null_resource.cluster_ready]

  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata = {
      name = "ca-central-1a"
    }
    spec = {
      subnet = data.aws_subnets.pod_subnets.ids[0]
      securityGroups = [aws_security_group.alb_sg.id]
    }
  }
}

resource "kubernetes_manifest" "eni_config_b" {

  depends_on = [null_resource.cluster_ready]

  manifest = {
    apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
    kind       = "ENIConfig"
    metadata = {
      name = "ca-central-1b"
    }
    spec = {
      subnet = data.aws_subnets.pod_subnets.ids[1]
      securityGroups = [aws_security_group.alb_sg.id]
    }
  }
}