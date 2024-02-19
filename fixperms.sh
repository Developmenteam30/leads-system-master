#!/bin/bash
chmod -R g=rwX storage/framework storage/logs
chgrp -R nginx storage/framework storage/logs
