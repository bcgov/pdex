# cloudfront.tf
data "aws_cloudfront_cache_policy" "caching_optimized" {
  name = "Managed-CachingOptimized"
}

data "aws_cloudfront_cache_policy" "caching_disabled" {
  name = "Managed-CachingDisabled"
}

data "aws_cloudfront_origin_request_policy" "all_viewer_except_host" {
  name = "Managed-AllViewerExceptHostHeader"
}

data "aws_cloudfront_origin_request_policy" "cors_custom_origin" {
  name = "Managed-CORS-CustomOrigin"
}

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

    domain_name = "app.f2da56-prod.stratus.cloud.gov.bc.ca"
    origin_id   = random_integer.cf_origin_id.result
        
    custom_header {
      name  = "Pdex-Source"
      value = var.source_token
    }
  }

  enabled         = true
  is_ipv6_enabled = true
  comment         = "PDEX - PROD"
  
  default_cache_behavior {
    allowed_methods  = ["DELETE","GET","HEAD","OPTIONS","PATCH","POST","PUT"]
    cached_methods   = ["GET","HEAD"]
    target_origin_id = random_integer.cf_origin_id.result

    viewer_protocol_policy = "redirect-to-https"

    # Dynamic app traffic: don't cache
    cache_policy_id          = data.aws_cloudfront_cache_policy.caching_disabled.id

    # Forward what the app might need (cookies, headers, querystrings)
    origin_request_policy_id = data.aws_cloudfront_origin_request_policy.all_viewer_except_host.id

    # Keep your CORS headers policy if you need it
    response_headers_policy_id = "60669652-455b-4ae9-85a4-c4c02393f86c"
  }
  # ordered_cache_behavior {
  #   path_pattern     = "/js/*"
  #   target_origin_id = random_integer.cf_origin_id.result

  #   allowed_methods  = ["GET", "HEAD", "OPTIONS"]
  #   cached_methods   = ["GET", "HEAD", "OPTIONS"]

  #   viewer_protocol_policy = "redirect-to-https"

  #   # Static content: cache it
  #   cache_policy_id = data.aws_cloudfront_cache_policy.caching_optimized.id

  #   # CORS/static: forward minimal
  #   origin_request_policy_id = data.aws_cloudfront_origin_request_policy.cors_custom_origin.id

  #   response_headers_policy_id = "60669652-455b-4ae9-85a4-c4c02393f86c"
  # }

  # ordered_cache_behavior {
  #   path_pattern     = "/css/*"
  #   target_origin_id = random_integer.cf_origin_id.result

  #   allowed_methods  = ["GET", "HEAD", "OPTIONS"]
  #   cached_methods   = ["GET", "HEAD", "OPTIONS"]

  #   viewer_protocol_policy = "redirect-to-https"

  #   # Static content: cache it
  #   cache_policy_id = data.aws_cloudfront_cache_policy.caching_optimized.id

  #   # CORS/static: forward minimal
  #   origin_request_policy_id = data.aws_cloudfront_origin_request_policy.cors_custom_origin.id

  #   response_headers_policy_id = "60669652-455b-4ae9-85a4-c4c02393f86c"
  # }


  price_class = "PriceClass_100"

  restrictions {
    geo_restriction {
      restriction_type = "none"
      locations        = []
    }
  }

  tags = var.common_tags

  aliases = ["pdex.gov.bc.ca"]

  viewer_certificate {
    acm_certificate_arn = "arn:aws:acm:ca-central-1:868987904026:certificate/9af1af37-a4fb-46ac-9d23-f9ec9501e0b5"
    ssl_support_method = "sni-only"
  }
}

output "cloudfront_url" {
  value = var.cloudfront ? "https://${aws_cloudfront_distribution.pdex-cer[0].domain_name}" : "CloudFront disabled"
}
