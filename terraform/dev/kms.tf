resource "aws_kms_key" "pdex-kms-key" {
  description             = "KMS Key for pdex"
  deletion_window_in_days = 10
  enable_key_rotation     = true
  tags = var.common_tags
  
  lifecycle {
    ignore_changes = all
  }
}