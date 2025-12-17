resource "aws_kms_key" "workbc-kms-key" {
  description             = "KMS Key for WorkBC"
  deletion_window_in_days = 10
  enable_key_rotation     = true
  tags = var.common_tags
}