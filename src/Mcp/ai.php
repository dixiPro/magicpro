<?php

use Laravel\Mcp\Facades\Mcp;
use MagicProSrc\Mcp\Servers\MagicProServer;
use MagicProSrc\Mcp\TokenMiddleware;

// the agent that lives on the server itself and speaks through stdio
Mcp::local('magicpro', MagicProServer::class);

// and the same server over https, for an agent on somebody's own machine: the
// site is reachable by these tools and by nothing else, there is no shell here
Mcp::web('/mcp/magicpro', MagicProServer::class)->middleware(TokenMiddleware::class);
