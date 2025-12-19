data "aws_region" "current" {}
data "aws_caller_identity" "current" {}

resource "aws_iam_service_linked_role" "es" {
	aws_service_name = "es.amazonaws.com"
	
	lifecycle {
		ignore_changes = all
	}
}

# OpenSearch credentials from Secrets Manager
data "aws_secretsmanager_secret_version" "opensearch_creds" {
  secret_id = "pdex-opensearch-creds"
}

locals {
  opensearch_creds = jsondecode(
    data.aws_secretsmanager_secret_version.opensearch_creds.secret_string
  )
}


resource "aws_elasticsearch_domain" "pdex-jb-cluster" {
	domain_name	= "pdex-jb-cluster"
	elasticsearch_version = "OpenSearch_3.3"
	
	cluster_config {
		instance_count = 1
		instance_type = "t3.medium.elasticsearch"
		zone_awareness_enabled = false
	}
	
	vpc_options {
		subnet_ids = [
			sort(data.aws_subnets.app.ids)[0]
		]

		security_group_ids = [aws_security_group.allow_tls.id]
	}
	
	advanced_options = {
		"rest.action.multi.allow_explicit_index" = "true"
	}
	
	access_policies = <<EOF
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Action": "es:*",
            "Principal": "*",
            "Effect": "Allow",
            "Resource": "arn:aws:es:${data.aws_region.current.id}:${data.aws_caller_identity.current.account_id}:domain/pdex-jb-cluster/*"
        }
    ]
}
EOF
	
	node_to_node_encryption {
		enabled = true
	}
	
	encrypt_at_rest {
		enabled = true
	}
	
	domain_endpoint_options {
		enforce_https = true
		tls_security_policy = "Policy-Min-TLS-1-2-2019-07"
	}
	
	ebs_options {
		ebs_enabled = true
		volume_size = 10
		volume_type = "gp3"
		throughput = 125
	}
	
	advanced_security_options {
		enabled = true
		internal_user_database_enabled = true
		master_user_options {
			master_user_name = local.opensearch_creds.es_username
			master_user_password = local.opensearch_creds.es_password
		}
	}
	
	tags = {
		Domain = "PdexJBCluster"
	}
	
	depends_on = [aws_iam_service_linked_role.es]
	
	lifecycle {
		ignore_changes = all
	}
}

