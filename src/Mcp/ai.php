<?php

use Laravel\Mcp\Facades\Mcp;
use MagicProSrc\Mcp\Servers\MagicProServer;

Mcp::local('magicpro', MagicProServer::class);
