resource "aws_s3_bucket" "pdex_s3" {
  bucket = "pdex-bucket"
}

#resource "aws_s3_bucket_acl" "pdex_s3_acl" {
#  bucket = aws_s3_bucket.pdex_s3.id
#  acl    = "private"
#}

resource "aws_s3_bucket_policy" "allow_access_from_cloudfront" {
  bucket = aws_s3_bucket.pdex_s3.id
  policy = data.aws_iam_policy_document.allow_access_from_cloudfront.json
}

data "aws_iam_policy_document" "allow_access_from_cloudfront" {

  statement {
  
    sid = "AllowCloudFrontServicePrincipal"
    
    principals {
      type        = "Service"
      identifiers = ["cloudfront.amazonaws.com"]
    }
	  
    actions = ["s3:GetObject"]

    resources = [
      "${aws_s3_bucket.pdex_s3.arn}/*",
    ]
	
	condition {
	  test = "StringEquals"
	  variable = "AWS:SourceArn"
	  #values = ["${aws_cloudfront_distribution.pdex[0].arn}"]
	  values = ["${aws_cloudfront_distribution.pdex-main[0].arn}", "arn:aws:cloudfront::396067939651:distribution/E1GDYI52ISK5MS", "arn:aws:cloudfront::318574063652:distribution/E2Y9BDYHIX4JX3"]
	}
  }

}
