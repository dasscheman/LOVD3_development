                                <?php exit(); ?>
#################### DO NOT MODIFY OR REMOVE THE LINE ABOVE ####################
################################################################################
#                              LOVD settings file                              #
#                                    v. 3.0                                    #
################################################################################
#                                                                              #
# Lines starting with # are comments and ignored by LOVD, as are empty lines.  #
#                                                                              #
# Default values of directives are mentioned when applicable. To keep the      #
# default settings, leave the line untouched.                                  #
#                                                                              #
################################################################################



[database]

# Database driver. Defaults to 'mysql'.
driver = mysql

# Database host. Defaults to 'localhost'.
#
hostname = localhost

# Database username and password (required for MySQL).
#
username = lovd
password = lovd_pw

# Database name (required). When using SQLite, specify the filename here.
#
#database = lovd3
database = lovd3_development

# This is for the table prefixes; if you wish to install more than one LOVD
# system per database, use different directories for these installations and
# change the setting below to a unique value.
# Please use alphanumeric characters only. Defaults to 'lovd'.
#
table_prefix = lovd_v3
#table_prefix = randomprefix
# (test alternative is lovd_v33, stress install is lovd_stress)