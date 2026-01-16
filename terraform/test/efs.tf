resource "aws_efs_file_system" "pdex-cer" {
  creation_token                  = "pdex-cer-efs"
  encrypted                       = true

  tags = merge(
    {
      Name = "pdex-cer-efs"
    },
    var.common_tags
  )
}

resource "aws_efs_mount_target" "data_azA" {
  file_system_id  = aws_efs_file_system.pdex-cer.id
  subnet_id       = sort(data.aws_subnets.data.ids)[0]
  security_groups = [data.aws_security_group.app.id, aws_security_group.allow_nfs.id]
  depends_on = [aws_security_group.allow_nfs]
}

resource "aws_efs_mount_target" "data_azB" {
  file_system_id  = aws_efs_file_system.pdex-cer.id
  subnet_id       = sort(data.aws_subnets.data.ids)[1]
  security_groups = [data.aws_security_group.app.id, aws_security_group.allow_nfs.id]
  depends_on = [aws_security_group.allow_nfs]
}
  
resource "aws_efs_backup_policy" "pdex-cer-efs-backups-policy" {
  file_system_id = aws_efs_file_system.pdex-cer.id

  backup_policy {
    status = "ENABLED"
  }
}
