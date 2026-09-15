<?php

namespace MagicProSrc\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProAdminControllers\API_ArticlesPostController;

#[Name('save-article')]
#[Description('Saves one MagicPro article. Only id is required and a field that is not sent keeps its value, but a field that is sent is replaced whole: send body and the entire blade of the article becomes what you sent, send controller and the entire controller does. So read the article with get-article first and change what came back from the base, not the text of your own previous save — otherwise you wipe what somebody edited in the admin panel meanwhile. Calls the articles API command saveById, which rewrites the generated blade and controller files under storage/dataMagicPro, so use this instead of a direct SQL update. Position in the tree is not here: it is changed by move-article. Returns the saved article as structured content. Errors from the API are returned as tool errors.')]
class SaveArticleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'article' => $schema->object([
                'id' => $schema->integer()
                    ->description('Id of the article to save. Must already exist.')
                    ->required(),

                'name' => $schema->string()
                    ->description('Article name, also its route: name=about gives /about. Must be unique. The article with id=1 must keep the name "root". With useController the name is also the controller class name, so no hyphen and no leading digit: price_list, not price-list.')
                    ->pattern('^[A-Za-z0-9_-]+$'),

                'title' => $schema->string()
                    ->description('Article title, commonly rendered in the html <title> tag.'),

                'controller' => $schema->string()
                    ->description('PHP source of the article controller, written to storage/dataMagicPro/controller/{name}.php. Empty when the article renders its view directly.'),

                'body' => $schema->string()
                    ->description('Blade source of the article, written to storage/dataMagicPro/view/{name}.blade.php. Other articles are referenced with the magic:: prefix, for example @include(\'magic::test\').'),

                'menuOn' => $schema->boolean()
                    ->description('Whether the article takes part in menus.'),

                'isRoute' => $schema->boolean()
                    ->description('Whether the article is published as a route.'),

                'routeParams' => $schema->object([
                    'useController' => $schema->boolean()
                        ->description('True passes control to the article controller, which then renders the view. False renders the view directly.'),

                    'adminOnly' => $schema->boolean()
                        ->description('Only an administrator may see the article.'),

                    'utmParamsEnable' => $schema->boolean()
                        ->description('Allow utm parameters. When false their presence causes a 404. The allowed list is configured in the admin panel under Setup. Defaults to true.'),

                    'getEnable' => $schema->boolean()
                        ->description('Allow any GET parameters, passed either as /key/value or as key=value. Defaults to false.'),

                    'postEnable' => $schema->boolean()
                        ->description('Handle the route as POST. GET requests then raise no error but carry no parameters, and POST has to be handled by the controller itself.'),

                    'bindKeys' => $schema->boolean()
                        ->description('True binds the values in keysArr by their position in the url: with keysArr ["first"], /test/1 is valid and /test/first/1 is not.'),

                    'keysArr' => $schema->array()
                        ->items($schema->string())
                        ->description('Names of the accepted GET parameters. An empty array accepts any. utm parameters are handled separately and never listed here.'),
                ])->description('Routing options, stored as JSON in the routeParams column. Taken key by key: a key that is not sent keeps its current value, keysArr is replaced whole.'),
            ])
                ->description('Fields to write. Only id is required; every omitted field keeps its current value. The position in the tree is not editable here — use move-article for parentId and npp.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = API_ArticlesPostController::run([
            'command' => 'saveById',
            'article' => $request->get('article'),
        ]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured($result['data']);
    }
}
