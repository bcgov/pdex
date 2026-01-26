# WAF Web ACL for CloudFront - IP allowlist for BC Gov networks
resource "aws_wafv2_ip_set" "bcgov_networks" {
  count    = var.cloudfront ? 1 : 0
  provider = aws.us-east-1  # WAF for CloudFront must be in us-east-1
  
  name               = "bcgov-networks-dev"
  description        = "BC Government network IP ranges"
  scope              = "CLOUDFRONT"
  ip_address_version = "IPV4"
  
  addresses = [
    "142.22.0.0/16",
    "142.23.0.0/16",
    "142.24.0.0/16",
    "142.25.0.0/16",
    "142.26.0.0/16",
    "142.27.0.0/16",
    "142.28.0.0/16",
    "142.29.0.0/16",
    "142.30.0.0/16",
    "142.31.0.0/16",
    "142.32.0.0/16",
    "142.33.0.0/16",
    "142.34.0.0/16",
    "142.35.0.0/16",
    "142.36.0.0/16",
    "142.37.0.0/16"
  ]
  
  tags = var.common_tags
}

resource "aws_wafv2_web_acl" "pdex_cloudfront" {
  count    = var.cloudfront ? 1 : 0
  provider = aws.us-east-1  # WAF for CloudFront must be in us-east-1
  
  name        = "pdex-cloudfront-dev"
  description = "WAF for PDEX CloudFront - Allow BC Gov networks only"
  scope       = "CLOUDFRONT"

  default_action {
    allow {}
  }

  rule {
    name     = "BlockNonBCGovNetworks"
    priority = 1

    action {
      block {}
    }

    statement {
      not_statement {
        statement {
          ip_set_reference_statement {
            arn = aws_wafv2_ip_set.bcgov_networks[0].arn
          }
        }
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "BlockNonBCGovNetworks"
      sampled_requests_enabled   = true
    }
  }

  visibility_config {
    cloudwatch_metrics_enabled = true
    metric_name                = "PDEXCloudFrontWAF"
    sampled_requests_enabled   = true
  }

  tags = var.common_tags
}
