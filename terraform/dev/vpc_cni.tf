# data "aws_subnets" "pod_subnets" {
#   filter {
#     name   = "tag:Name"
#     values = ["BCGOV-LZA-extended-app-ca-central-1a", "BCGOV-LZA-extended-app-ca-central-1b"]
#   }
# }

# # "Which subnet + security group should I use to create the pod ENI for this node?"
# resource "null_resource" "eni_config_a" {
#   triggers = {
#     subnet_id = data.aws_subnets.pod_subnets.ids[0]
#     sg_id     = data.aws_security_group.data.id
#   }

#   depends_on = [null_resource.cluster_ready]

#   provisioner "local-exec" {
#     command = <<EOT
# kubectl apply -f - <<'ENDYAML'
# apiVersion: crd.k8s.amazonaws.com/v1alpha1
# kind: ENIConfig
# metadata:
#   name: ca-central-1a
# spec:
#   subnet: ${data.aws_subnets.pod_subnets.ids[0]}
#   securityGroups:
#   - ${data.aws_security_group.data.id}
# ENDYAML
# EOT
#   }
# }

# resource "null_resource" "eni_config_b" {
#   triggers = {
#     subnet_id = data.aws_subnets.pod_subnets.ids[1]
#     sg_id     = data.aws_security_group.data.id
#   }

#   depends_on = [null_resource.cluster_ready]

#   provisioner "local-exec" {
#     command = <<EOT
# kubectl apply -f - <<'ENDYAML'
# apiVersion: crd.k8s.amazonaws.com/v1alpha1
# kind: ENIConfig
# metadata:
#   name: ca-central-1b
# spec:
#   subnet: ${data.aws_subnets.pod_subnets.ids[1]}
#   securityGroups:
#   - ${data.aws_security_group.data.id}
# ENDYAML
# EOT
#   }
# }