API did not recognize GroupVersionKind from manifest (CRD may not be installed)

kubectl apply -k \
  "github.com/kubernetes-sigs/aws-load-balancer-controller/config/crd?ref=v2.6.2"

  Verify CRDs are installed
kubectl get crds | grep elbv2.k8s.aws

You must see output similar to:
targetgroupbindings.elbv2.k8s.aws
ingressclassparams.elbv2.k8s.aws