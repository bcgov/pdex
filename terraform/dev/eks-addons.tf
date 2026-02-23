data "aws_eks_cluster" "this" {
  name = aws_eks_cluster.pdex-cluster.name
}

# Helm provider uses exec-based token generation so credentials are fetched
# lazily at apply time (not during plan, when the cluster may not exist yet).
provider "helm" {
  kubernetes = {
    host                   = data.aws_eks_cluster.this.endpoint
    cluster_ca_certificate = base64decode(data.aws_eks_cluster.this.certificate_authority[0].data)
    exec = {
      api_version = "client.authentication.k8s.io/v1beta1"
      command     = "aws"
      args        = ["eks", "get-token", "--cluster-name", aws_eks_cluster.pdex-cluster.name, "--region", var.aws_region]
    }
  }
}

# Wait for the cluster to be fully active and configure local kubeconfig so
# subsequent null_resource provisioners (kubectl apply) can reach the API.
resource "null_resource" "cluster_ready" {
  triggers = {
    cluster_name = aws_eks_cluster.pdex-cluster.name
  }

  provisioner "local-exec" {
    command = <<-EOT
      aws eks wait cluster-active --name ${aws_eks_cluster.pdex-cluster.name} --region ${var.aws_region}
      aws eks update-kubeconfig --name ${aws_eks_cluster.pdex-cluster.name} --region ${var.aws_region}
    EOT
  }

  depends_on = [aws_eks_cluster.pdex-cluster]
}

resource "helm_release" "aws_load_balancer_controller" {
  name         = "aws-load-balancer-controller"
  namespace    = "kube-system"
  repository   = "https://aws.github.io/eks-charts"
  chart        = "aws-load-balancer-controller"
  version      = "1.7.2"
  wait         = true
  timeout      = 1200
  force_update = true  # override pending-upgrade lock left by a failed run

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

  lifecycle {
    # Prevent Terraform from upgrading an already-running release.
    # repository + chart are left un-ignored so the provider can locate the chart during plan.
    ignore_changes = [version, values, set]
  }
}

# need metrics server for HPA to work
resource "helm_release" "metrics_server" {
  name       = "metrics-server"
  namespace  = "kube-system"
  repository = "https://kubernetes-sigs.github.io/metrics-server/"
  chart      = "metrics-server"
  version    = "3.12.1" # pick a stable version; you can bump later
  wait       = true
  timeout    = 1200
  force_update = true  # override pending-upgrade lock left by a failed run

  # EKS commonly needs these kubelet flags to avoid metrics not available
  set = [
    {
      name  = "args[0]"
      value = "--kubelet-insecure-tls"
    },
    {
      name  = "args[1]"
      value = "--kubelet-preferred-address-types=InternalIP\\,ExternalIP\\,Hostname"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_addon.coredns-addon
  ]

  lifecycle {
    ignore_changes = [version, values, set]
  }
}

# need VPA for vertical pod autoscaling, this is set for Recommendation only mode
resource "helm_release" "vpa" {
  name             = "vpa"
  namespace        = "kube-system"
  repository       = "https://charts.fairwinds.com/stable"
  chart            = "vpa"
  version          = "4.5.0"
  wait             = true
  timeout          = 1200
  cleanup_on_fail  = true  # delete stale hook jobs (e.g. vpa-admission-certgen) before retrying
  force_update     = true  # override pending-upgrade lock left by a failed run

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_addon.coredns-addon
  ]

  lifecycle {
    ignore_changes = [version, values, set]
  }
}

# Cluster Autoscaler Helm Release to see nodes scaling beyond desired count and up to max count
resource "helm_release" "cluster_autoscaler" {
  name       = "cluster-autoscaler"
  namespace  = "kube-system"
  repository = "https://kubernetes.github.io/autoscaler"
  chart      = "cluster-autoscaler"
  version    = "9.37.0"
  wait       = true
  timeout    = 1200
  force_update = true  # override pending-upgrade lock left by a failed run

  set = [
    {
      name  = "autoDiscovery.clusterName"
      value = aws_eks_cluster.pdex-cluster.name
    },
    {
      name  = "awsRegion"
      value = var.aws_region
    },
    {
      name  = "rbac.serviceAccount.name"
      value = "cluster-autoscaler"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_pod_identity_association.cluster_autoscaler
  ]

  lifecycle {
    ignore_changes = [version, values, set]
  }
}