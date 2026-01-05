# network.tf

data "aws_vpc" "main" {
  id = var.vpc_id
}

data "aws_subnets" "app" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.app_subnet_names
  }
}

data "aws_subnets" "data" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.data_subnet_names
  }
}

data "aws_subnets" "web" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.main.id]
  }

  filter {
    name   = "tag:Name"
    values = local.web_subnet_names
  }
}

data "aws_subnet" "app" {
  for_each = toset(data.aws_subnets.app.ids)
  id       = each.value
}

data "aws_subnet" "data" {
  for_each = toset(data.aws_subnets.data.ids)
  id       = each.value
}

data "aws_subnet" "web" {
  for_each = toset(data.aws_subnets.web.ids)
  id       = each.value
}

# Internet Gateway for public ALB
resource "aws_internet_gateway" "main" {
  vpc_id = data.aws_vpc.main.id

  tags = merge(
    var.common_tags,
    {
      Name = "pdex-igw"
    }
  )
}

# Route table for public subnets
resource "aws_route_table" "public" {
  vpc_id = data.aws_vpc.main.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main.id
  }

  tags = merge(
    var.common_tags,
    {
      Name = "pdex-public-rt"
    }
  )
}

# Associate route table with web subnets
resource "aws_route_table_association" "web_public" {
  for_each = toset(data.aws_subnets.web.ids)
  
  subnet_id      = each.value
  route_table_id = aws_route_table.public.id
}
