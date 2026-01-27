# WAF Web ACL for CloudFront - Allow Canada traffic only
# THIS SHOULD BE USED ONLY ON DEV AND TESTING ENVIRONMENTS
resource "aws_wafv2_web_acl" "pdex_cloudfront" {
  count    = var.cloudfront ? 1 : 0
  provider = aws.us-east-1  # WAF for CloudFront must be in us-east-1
  
  name        = "pdex-cloudfront-test"
  description = "WAF for PDEX CloudFront - Allow Canada traffic only"
  scope       = "CLOUDFRONT"

  default_action {
    block {
      custom_response {
        response_code = 403
      }
    }
  }

  rule {
    name     = "canada_traffic"
    priority = 2

    action {
      allow {}
    }

    statement {
      geo_match_statement {
        country_codes = ["CA"]
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "canada_traffic"
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
