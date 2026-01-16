# cloudfront.tf
resource "random_integer" "cf_origin_id" {
  min = 1
  max = 100
}

resource "aws_cloudfront_distribution" "pdex-cer" {

  count = var.cloudfront ? 1 : 0

  origin {
    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "https-only"
      origin_ssl_protocols = ["TLSv1.2"]
    }

    domain_name = "app.f2da56-test.stratus.cloud.gov.bc.ca"
    origin_id   = random_integer.cf_origin_id.result
        
    custom_header {
      name  = "Pdex-Source"
      value = var.source_token
    }
  }

  enabled         = true
  is_ipv6_enabled = true
  comment         = "PDEX - TEST"
  
  default_cache_behavior {
    allowed_methods = [
      "DELETE",
      "GET",
      "HEAD",
      "OPTIONS",
      "PATCH",
      "POST",
    "PUT"]
    cached_methods = ["GET", "HEAD"]

    target_origin_id = random_integer.cf_origin_id.result

    forwarded_values {
      query_string = true

      cookies {
        forward = "all"
      }
    }

    viewer_protocol_policy = "redirect-to-https"
    min_ttl                = 0
    default_ttl            = 3600
    max_ttl                = 86400
	
    # SimpleCORS
    response_headers_policy_id = "60669652-455b-4ae9-85a4-c4c02393f86c"
  }

  price_class = "PriceClass_100"

  restrictions {
    geo_restriction {
      restriction_type = "none"
      locations        = []
    }
  }

  tags = var.common_tags

  aliases = ["test.pdex.gov.bc.ca"]

  viewer_certificate {
    acm_certificate_arn = var.cloudfront_certificate_arn
    ssl_support_method = "sni-only"
  }
}

output "cloudfront_url" {
  value = var.cloudfront ? "https://${aws_cloudfront_distribution.pdex-cer[0].domain_name}" : "CloudFront disabled"
}
