ssh -i ~/Sites/vibbidi/DB/ec2-proxy2-glue-th.pem ec2-user@proxy2.glue-th.com
mysql -h 10.0.1.128 -usysadm -psysadmpasswd
mysqldump -h 10.0.1.128 -usysadm -psysadmpasswd --routines V4 > V4-justin.sql

