# data "aws_subnets" "pod_subnets" {
#   filter {
#     name   = "tag:Name"
#     values = ["BCGOV-LZA-extended-app-ca-central-1a", "BCGOV-LZA-extended-app-ca-central-1b"]
#   }
# }

# # Which subnet + security group should I use to create the pod ENI for this node?
# resource "kubernetes_manifest" "eni_config_a" {
#   manifest = {
#     apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
#     kind       = "ENIConfig"
#     metadata   = { name = "ca-central-1a" }
#     spec = {
#       subnet         = data.aws_subnets.pod_subnets.ids[0]
#       securityGroups = [data.aws_security_group.eks_node_sg.id]
#     }
#   }
# }

# resource "kubernetes_manifest" "eni_config_b" {
#   manifest = {
#     apiVersion = "crd.k8s.amazonaws.com/v1alpha1"
#     kind       = "ENIConfig"
#     metadata   = { name = "ca-central-1b" }
#     spec = {
#       subnet         = data.aws_subnets.pod_subnets.ids[1]
#       securityGroups = [data.aws_security_group.eks_node_sg.id]
#     }
#   }
# }
