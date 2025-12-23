
locals {
  common_tags        = var.common_tags
  environment		 = var.vpc_name
  availability_zones = ["A", "B"]
  app_subnet_names   = [for az in local.availability_zones : "${local.environment}-App-${az}"]
  data_subnet_names   = [for az in local.availability_zones : "${local.environment}-Data-${az}"]
  web_subnet_names   = [for az in local.availability_zones : "${local.environment}-Web-MainTgwAttach-${az}"]
}
