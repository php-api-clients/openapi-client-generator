<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use RingCentral\Psr7\Request;

final class WebHook
{
    /**
     * @param string $path
     * @param string $namespace
     * @param string $baseNamespace
     * @param string $className
     * @param PathItem $pathItem
     * @return iterable<Node>
     * @throws \Jawira\CaseConverter\CaseConverterException
     */
    public static function generate(string $path, string $namespace, string $baseNamespace, string $className, PathItem $pathItem, array $schemaClassNameMap, string $rootNamespace): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->implement('\\' . $baseNamespace . 'WebHookInterface');

        $method = $factory->method('resolve')->makePublic()->setReturnType('string')->addParam(
            (new Param('data'))->setType('array')
        );
        if ($pathItem->post->requestBody->content !== null) {
            $content = current($pathItem->post->requestBody->content);
            $tmts = [];
            if ($content->schema->oneOf !== null && count($content->schema->oneOf) > 0) {
                $tmts[] = new Node\Expr\Assign(new Node\Expr\Variable('schemaValidator'), new Node\Expr\New_(
                    new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                    [
                        new Node\Arg(new Node\Expr\ClassConstFetch(
                            new Node\Name('\League\OpenAPIValidation\Schema\SchemaValidator'),
                            new Node\Name('VALIDATE_AS_REQUEST'),
                        ))
                    ]
                ));
                $gotoLabels = 'a';

                if ($content->schema->discriminator !== null && $content->schema->discriminator->propertyName !== null && strlen($content->schema->discriminator->propertyName) > 0) {
                    foreach ($content->schema->oneOf as $oneOfSchema) {
                        foreach ($oneOfSchema->properties as $name => $property) {
                            if ($content->schema->discriminator->propertyName === $name) {
                                $tmts[] = new Node\Stmt\Label($gotoLabels);
                                $gotoLabels++;
                                //'{"title":"release created event","required":["action","release","repository","sender"],"type":"object","properties":{"action":{"enum":["created"],"type":"string"},"release":{"required":["url","assets_url","upload_url","html_url","id","node_id","tag_name","target_commitish","name","draft","author","prerelease","created_at","published_at","assets","tarball_url","zipball_url","body"],"type":"object","properties":{"url":{"type":"string","format":"uri"},"assets_url":{"type":"string","format":"uri"},"upload_url":{"type":"string","format":"uri-template"},"html_url":{"type":"string","format":"uri"},"id":{"type":"integer"},"node_id":{"type":"string"},"tag_name":{"type":"string","description":"The name of the tag."},"target_commitish":{"type":"string","description":"Specifies the commitish value that determines where the Git tag is created from."},"name":{"type":"null"},"draft":{"type":"boolean","description":"true to create a draft (unpublished) release, false to create a published one."},"author":{"title":"User","required":["login","id","node_id","avatar_url","gravatar_id","url","html_url","followers_url","following_url","gists_url","starred_url","subscriptions_url","organizations_url","repos_url","events_url","received_events_url","type","site_admin"],"type":"object","properties":{"login":{"type":"string"},"id":{"type":"integer"},"node_id":{"type":"string"},"name":{"type":"string"},"email":{"type":["string","null"]},"avatar_url":{"type":"string","format":"uri"},"gravatar_id":{"type":"string"},"url":{"type":"string","format":"uri"},"html_url":{"type":"string","format":"uri"},"followers_url":{"type":"string","format":"uri"},"following_url":{"type":"string","format":"uri-template"},"gists_url":{"type":"string","format":"uri-template"},"starred_url":{"type":"string","format":"uri-template"},"subscriptions_url":{"type":"string","format":"uri"},"organizations_url":{"type":"string","format":"uri"},"repos_url":{"type":"string","format":"uri"},"events_url":{"type":"string","format":"uri-template"},"received_events_url":{"type":"string","format":"uri"},"type":{"enum":["Bot","User","Organization"],"type":"string"},"site_admin":{"type":"boolean"}},"additionalProperties":false},"prerelease":{"type":"boolean","description":"Whether the release is identified as a prerelease or a full release."},"created_at":{"type":["string","null"],"format":"date-time"},"published_at":{"type":["string","null"],"format":"date-time"},"assets":{"type":"array","items":{"title":"Release Asset","required":["url","browser_download_url","id","node_id","name","label","state","content_type","size","download_count","created_at","updated_at"],"type":"object","properties":{"url":{"type":"string","format":"uri"},"browser_download_url":{"type":"string","format":"uri"},"id":{"type":"integer"},"node_id":{"type":"string"},"name":{"type":"string","description":"The file name of the asset."},"label":{"type":"string"},"state":{"enum":["uploaded"],"type":"string","description":"State of the release asset."},"content_type":{"type":"string"},"size":{"type":"integer"},"download_count":{"type":"integer"},"created_at":{"type":"string","format":"date-time"},"updated_at":{"type":"string","format":"date-time"},"uploader":{"title":"User","required":["login","id","node_id","avatar_url","gravatar_id","url","html_url","followers_url","following_url","gists_url","starred_url","subscriptions_url","organizations_url","repos_url","events_url","received_events_url","type","site_admin"],"type":"object","properties":{"login":{"type":"string"},"id":{"type":"integer"},"node_id":{"type":"string"},"name":{"type":"string"},"email":{"type":["string","null"]},"avatar_url":{"type":"string","format":"uri"},"gravatar_id":{"type":"string"},"url":{"type":"string","format":"uri"},"html_url":{"type":"string","format":"uri"},"followers_url":{"type":"string","format":"uri"},"following_url":{"type":"string","format":"uri-template"},"gists_url":{"type":"string","format":"uri-template"},"starred_url":{"type":"string","format":"uri-template"},"subscriptions_url":{"type":"string","format":"uri"},"organizations_url":{"type":"string","format":"uri"},"repos_url":{"type":"string","format":"uri"},"events_url":{"type":"string","format":"uri-template"},"received_events_url":{"type":"string","format":"uri"},"type":{"enum":["Bot","User","Organization"],"type":"string"},"site_admin":{"type":"boolean"}},"additionalProperties":false}},"description":"Data related to a release.","additionalProperties":false}},"tarball_url":{"type":["string","null"],"format":"uri"},"zipball_url":{"type":["string","null"],"format":"uri"},"body":{"type":["string","null"]}},"description":"The [release](https:\\/\\/docs.github.com\\/en\\/rest\\/reference\\/repos\\/#get-a-release) object.","additionalProperties":false},"repository":{"title":"Repository","required":["id","node_id","name","full_name","private","owner","html_url","description","fork","url","forks_url","keys_url","collaborators_url","teams_url","hooks_url","issue_events_url","events_url","assignees_url","branches_url","tags_url","blobs_url","git_tags_url","git_refs_url","trees_url","statuses_url","languages_url","stargazers_url","contributors_url","subscribers_url","subscription_url","commits_url","git_commits_url","comments_url","issue_comment_url","contents_url","compare_url","merges_url","archive_url","downloads_url","issues_url","pulls_url","milestones_url","notifications_url","labels_url","releases_url","deployments_url","created_at","updated_at","pushed_at","git_url","ssh_url","clone_url","svn_url","homepage","size","stargazers_count","watchers_count","language","has_issues","has_projects","has_downloads","has_wiki","has_pages","forks_count","mirror_url","archived","open_issues_count","license","forks","open_issues","watchers","default_branch"],"type":"object","properties":{"id":{"type":"integer","description":"Unique identifier of the repository"},"node_id":{"type":"string"},"name":{"type":"string","description":"The name of the repository."},"full_name":{"type":"string"},"private":{"type":"boolean","description":"Whether the repository is private or public."},"owner":{"title":"User","required":["login","id","node_id","avatar_url","gravatar_id","url","html_url","followers_url","following_url","gists_url","starred_url","subscriptions_url","organizations_url","repos_url","events_url","received_events_url","type","site_admin"],"type":"object","properties":{"login":{"type":"string"},"id":{"type":"integer"},"node_id":{"type":"string"},"name":{"type":"string"},"email":{"type":["string","null"]},"avatar_url":{"type":"string","format":"uri"},"gravatar_id":{"type":"string"},"url":{"type":"string","format":"uri"},"html_url":{"type":"string","format":"uri"},"followers_url":{"type":"string","format":"uri"},"following_url":{"type":"string","format":"uri-template"},"gists_url":{"type":"string","format":"uri-template"},"starred_url":{"type":"string","format":"uri-template"},"subscriptions_url":{"type":"string","format":"uri"},"organizations_url":{"type":"string","format":"uri"},"repos_url":{"type":"string","format":"uri"},"events_url":{"type":"string","format":"uri-template"},"received_events_url":{"type":"string","format":"uri"},"type":{"enum":["Bot","User","Organization"],"type":"string"},"site_admin":{"type":"boolean"}},"additionalProperties":false},"html_url":{"type":"string","format":"uri"},"description":{"type":["string","null"]},"fork":{"type":"boolean"},"url":{"type":"string","format":"uri"},"forks_url":{"type":"string","format":"uri"},"keys_url":{"type":"string","format":"uri-template"},"collaborators_url":{"type":"string","format":"uri-template"},"teams_url":{"type":"string","format":"uri"},"hooks_url":{"type":"string","format":"uri"},"issue_events_url":{"type":"string","format":"uri-template"},"events_url":{"type":"string","format":"uri"},"assignees_url":{"type":"string","format":"uri-template"},"branches_url":{"type":"string","format":"uri-template"},"tags_url":{"type":"string","format":"uri"},"blobs_url":{"type":"string","format":"uri-template"},"git_tags_url":{"type":"string","format":"uri-template"},"git_refs_url":{"type":"string","format":"uri-template"},"trees_url":{"type":"string","format":"uri-template"},"statuses_url":{"type":"string","format":"uri-template"},"languages_url":{"type":"string","format":"uri"},"stargazers_url":{"type":"string","format":"uri"},"contributors_url":{"type":"string","format":"uri"},"subscribers_url":{"type":"string","format":"uri"},"subscription_url":{"type":"string","format":"uri"},"commits_url":{"type":"string","format":"uri-template"},"git_commits_url":{"type":"string","format":"uri-template"},"comments_url":{"type":"string","format":"uri-template"},"issue_comment_url":{"type":"string","format":"uri-template"},"contents_url":{"type":"string","format":"uri-template"},"compare_url":{"type":"string","format":"uri-template"},"merges_url":{"type":"string","format":"uri"},"archive_url":{"type":"string","format":"uri-template"},"downloads_url":{"type":"string","format":"uri"},"issues_url":{"type":"string","format":"uri-template"},"pulls_url":{"type":"string","format":"uri-template"},"milestones_url":{"type":"string","format":"uri-template"},"notifications_url":{"type":"string","format":"uri-template"},"labels_url":{"type":"string","format":"uri-template"},"releases_url":{"type":"string","format":"uri-template"},"deployments_url":{"type":"string","format":"uri"},"created_at":{"oneOf":[{"type":"integer"},{"type":"string","format":"date-time"}]},"updated_at":{"type":"string","format":"date-time"},"pushed_at":{"oneOf":[{"type":"integer"},{"type":"string","format":"date-time"},{"type":"null"}]},"git_url":{"type":"string","format":"uri"},"ssh_url":{"type":"string"},"clone_url":{"type":"string","format":"uri"},"svn_url":{"type":"string","format":"uri"},"homepage":{"type":["string","null"]},"size":{"type":"integer"},"stargazers_count":{"type":"integer"},"watchers_count":{"type":"integer"},"language":{"type":["string","null"]},"has_issues":{"type":"boolean","description":"Whether issues are enabled.","default":true},"has_projects":{"type":"boolean","description":"Whether projects are enabled.","default":true},"has_downloads":{"type":"boolean","description":"Whether downloads are enabled.","default":true},"has_wiki":{"type":"boolean","description":"Whether the wiki is enabled.","default":true},"has_pages":{"type":"boolean"},"forks_count":{"type":"integer"},"mirror_url":{"type":["string","null"],"format":"uri"},"archived":{"type":"boolean","description":"Whether the repository is archived.","default":false},"disabled":{"type":"boolean","description":"Returns whether or not this repository is disabled."},"open_issues_count":{"type":"integer"},"license":{"oneOf":[{"title":"License","required":["key","name","spdx_id","url","node_id"],"type":"object","properties":{"key":{"type":"string"},"name":{"type":"string"},"spdx_id":{"type":"string"},"url":{"type":["string","null"],"format":"uri"},"node_id":{"type":"string"}},"additionalProperties":false},{"type":"null"}]},"forks":{"type":"integer"},"open_issues":{"type":"integer"},"watchers":{"type":"integer"},"stargazers":{"type":"integer"},"default_branch":{"type":"string","description":"The default branch of the repository."},"allow_squash_merge":{"type":"boolean","description":"Whether to allow squash merges for pull requests.","default":true},"allow_merge_commit":{"type":"boolean","description":"Whether to allow merge commits for pull requests.","default":true},"allow_rebase_merge":{"type":"boolean","description":"Whether to allow rebase merges for pull requests.","default":true},"delete_branch_on_merge":{"type":"boolean","description":"Whether to delete head branches when pull requests are merged","default":false},"master_branch":{"type":"string"},"permissions":{"required":["pull","push","admin"],"type":"object","properties":{"pull":{"type":"boolean"},"push":{"type":"boolean"},"admin":{"type":"boolean"},"maintain":{"type":"boolean"},"triage":{"type":"boolean"}},"additionalProperties":false},"public":{"type":"boolean"},"organization":{"type":"string"}},"description":"A git repository","additionalProperties":false},"sender":{"title":"User","required":["login","id","node_id","avatar_url","gravatar_id","url","html_url","followers_url","following_url","gists_url","starred_url","subscriptions_url","organizations_url","repos_url","events_url","received_events_url","type","site_admin"],"type":"object","properties":{"login":{"type":"string"},"id":{"type":"integer"},"node_id":{"type":"string"},"name":{"type":"string"},"email":{"type":["string","null"]},"avatar_url":{"type":"string","format":"uri"},"gravatar_id":{"type":"string"},"url":{"type":"string","format":"uri"},"html_url":{"type":"string","format":"uri"},"followers_url":{"type":"string","format":"uri"},"following_url":{"type":"string","format":"uri-template"},"gists_url":{"type":"string","format":"uri-template"},"starred_url":{"type":"string","format":"uri-template"},"subscriptions_url":{"type":"string","format":"uri"},"organizations_url":{"type":"string","format":"uri"},"repos_url":{"type":"string","format":"uri"},"events_url":{"type":"string","format":"uri-template"},"received_events_url":{"type":"string","format":"uri"},"type":{"enum":["Bot","User","Organization"],"type":"string"},"site_admin":{"type":"boolean"}},"additionalProperties":false},"installation":{"title":"InstallationLite","required":["id","node_id"],"type":"object","properties":{"id":{"type":"integer","description":"The ID of the installation."},"node_id":{"type":"string"}},"description":"Installation","additionalProperties":false},"organization":{"title":"Organization","required":["login","id","node_id","url","repos_url","events_url","hooks_url","issues_url","members_url","public_members_url","avatar_url","description"],"type":"object","properties":{"login":{"type":"string"},"id":{"type":"integer"},"node_id":{"type":"string"},"url":{"type":"string","format":"uri"},"html_url":{"type":"string","format":"uri"},"repos_url":{"type":"string","format":"uri"},"events_url":{"type":"string","format":"uri"},"hooks_url":{"type":"string","format":"uri"},"issues_url":{"type":"string","format":"uri"},"members_url":{"type":"string","format":"uri-template"},"public_members_url":{"type":"string","format":"uri-template"},"avatar_url":{"type":"string","format":"uri"},"description":{"type":["string","null"]}},"additionalProperties":false}},"additionalProperties":false}'
                                $fabicatedSchema = [
                                    'title' => $oneOfSchema->title,
                                    'required' => [$content->schema->discriminator->propertyName],
                                    'properties' => [
                                        $name => $property->getSerializableData(),
                                    ],
                                    'additionalProperties' => true,
                                ];
                                $tmts[] = new Node\Stmt\TryCatch([
                                    new Node\Stmt\Expression(new Node\Expr\MethodCall(
                                        new Node\Expr\Variable('schemaValidator'),
                                        new Node\Name('validate'),
                                        [
                                            new Node\Arg(new Node\Expr\Variable('data')),
                                            new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), new Node\Name('readFromJson'), [new Node\Scalar\String_(json_encode($fabicatedSchema)), new Node\Scalar\String_('\cebe\openapi\spec\Schema')])),
                                        ]
                                    )),
                                    new Node\Stmt\Return_(new Node\Scalar\String_($rootNamespace . 'Schema\\' . $schemaClassNameMap[spl_object_hash($oneOfSchema)])),
                                ], [
                                    new Node\Stmt\Catch_(
                                        [new Node\Name('\\' . \Throwable::class)],
                                        new Node\Expr\Variable($gotoLabels),
                                        [
                                            new Node\Stmt\Goto_($gotoLabels),
                                        ]
                                    ),
                                ]);
                            }
                        }
                    }

                    $tmts[] = new Node\Stmt\Label($gotoLabels);
                    $tmts[] = new Node\Stmt\Throw_(new Node\Expr\Variable($gotoLabels));
                } else {
                    foreach ($content->schema->oneOf as $oneOfSchema) {
                        $tmts[] = new Node\Stmt\Label($gotoLabels);
                        $gotoLabels++;
                        $tmts[] = new Node\Stmt\TryCatch([
                            new Node\Stmt\Expression(new Node\Expr\MethodCall(
                                new Node\Expr\Variable('schemaValidator'),
                                new Node\Name('validate'),
                                [
                                    new Node\Arg(new Node\Expr\Variable('data')),
                                    new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), new Node\Name('readFromJson'), [new Node\Scalar\String_(json_encode($oneOfSchema->getSerializableData())), new Node\Scalar\String_('\cebe\openapi\spec\Schema')])),
                                ]
                            )),
                            new Node\Stmt\Return_(new Node\Scalar\String_($rootNamespace . 'Schema\\' . $schemaClassNameMap[spl_object_hash($oneOfSchema)])),
                        ], [
                            new Node\Stmt\Catch_(
                                [new Node\Name('\\' . \Throwable::class)],
                                new Node\Expr\Variable($gotoLabels),
                                [
                                    new Node\Stmt\Goto_($gotoLabels),
                                ]
                            ),
                        ]);
                    }
                    $tmts[] = new Node\Stmt\Label($gotoLabels);
                    $tmts[] = new Node\Stmt\Throw_(new Node\Expr\Variable($gotoLabels));
                }
            }

            if (count($tmts) === 0) {
                $tmts[] = new Node\Stmt\Return_(new Node\Scalar\String_($rootNamespace . 'Schema\\' . $schemaClassNameMap[spl_object_hash($content->schema)]));
            }

            $method->addStmts($tmts);
        }
        $class->addStmt($method);

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
