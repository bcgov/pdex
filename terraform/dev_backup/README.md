API did not recognize GroupVersionKind from manifest (CRD may not be installed)

kubectl apply -k \
  "github.com/kubernetes-sigs/aws-load-balancer-controller/config/crd?ref=v2.6.2"

  Verify CRDs are installed
kubectl get crds | grep elbv2.k8s.aws

You must see output similar to:
targetgroupbindings.elbv2.k8s.aws
ingressclassparams.elbv2.k8s.aws


aws elbv2 describe-target-groups --target-group-arns arn:aws:elasticloadbalancing:ca-central-1:814738839437:targetgroup/pdex-target-group/444d956cd91cd437

kubectl describe service pdex-service-dev -n default

kubectl logs -n kube-system deployment/aws-load-balancer-controller


The pods are still the ones created back at 00:00:55 before we removed the IRSA annotation. The current Deployment template (shown earlier) no longer has AWS_ROLE_ARN or the aws-iam-token volume, but the running pods do because they were never restarted. Just recycle them so they pick up the new spec:
kubectl rollout restart deployment/aws-load-balancer-controller -n kube-system
# or delete both pods:
kubectl delete pod -n kube-system -l app.kubernetes.io/name=aws-load-balancer-controller

Wait a minute for the new pods to start, then check:
kubectl get pods -n kube-system | grep aws-load-balancer
kubectl get pod -n kube-system <new-pod-name> -o yaml | grep -A5 AWS_ROLE_ARN

