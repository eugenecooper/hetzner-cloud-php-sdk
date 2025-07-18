<?php

/**
 * Created by PhpStorm.
 * User: lukaskammerling
 * Date: 28.01.18
 * Time: 20:52.
 */

namespace LKDev\HetznerCloud\Models\Servers;

use LKDev\HetznerCloud\APIResponse;
use LKDev\HetznerCloud\HetznerAPIClient;
use LKDev\HetznerCloud\Models\Actions\Action;
use LKDev\HetznerCloud\Models\Datacenters\Datacenter;
use LKDev\HetznerCloud\Models\Images\Image;
use LKDev\HetznerCloud\Models\Locations\Location;
use LKDev\HetznerCloud\Models\Meta;
use LKDev\HetznerCloud\Models\Model;
use LKDev\HetznerCloud\Models\Servers\Types\ServerType;
use LKDev\HetznerCloud\RequestOpts;
use LKDev\HetznerCloud\Traits\GetFunctionTrait;

class Servers extends Model
{
    use GetFunctionTrait;

    /**
     * @var array
     */
    protected $servers;

    /**
     * Returns all existing server objects.
     *
     * @see https://docs.hetzner.cloud/#resources-servers-get
     *
     * @param  RequestOpts|null  $requestOpts
     * @return array
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function all(?RequestOpts $requestOpts = null): array
    {
        if ($requestOpts == null) {
            $requestOpts = new ServerRequestOpts();
        }

        return $this->_all($requestOpts);
    }

    /**
     * List server objects.
     *
     * @see https://docs.hetzner.cloud/#resources-servers-get
     *
     * @param  RequestOpts|null  $requestOpts
     * @return APIResponse|null
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function list(?RequestOpts $requestOpts = null): ?APIResponse
    {
        if ($requestOpts == null) {
            $requestOpts = new ServerRequestOpts();
        }
        $response = $this->httpClient->get('servers'.$requestOpts->buildQuery());
        if (! HetznerAPIClient::hasError($response)) {
            $resp = json_decode((string) $response->getBody());

            return APIResponse::create([
                'meta' => Meta::parse($resp->meta),
                $this->_getKeys()['many'] => self::parse($resp->{$this->_getKeys()['many']})->{$this->_getKeys()['many']},
            ], $response->getHeaders());
        }

        return null;
    }

    /**
     * Returns a specific server object by its name. The server must exist inside the project.
     *
     * @see https://docs.hetzner.cloud/#resources-servers-get
     *
     * @param  string  $serverName
     * @return \LKDev\HetznerCloud\Models\Servers\Server|null
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function getByName(string $serverName): ?Server
    {
        $servers = $this->list(new ServerRequestOpts($serverName));

        return (count($servers->servers) > 0) ? $servers->servers[0] : null;
    }

    /**
     * Returns a specific server object by its id. The server must exist inside the project.
     *
     * @see https://docs.hetzner.cloud/#resources-servers-get
     *
     * @param  int  $serverId
     * @return \LKDev\HetznerCloud\Models\Servers\Server|null
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function getById(int $serverId): ?Server
    {
        $response = $this->httpClient->get('servers/'.$serverId);
        if (! HetznerAPIClient::hasError($response)) {
            return Server::parse(json_decode((string) $response->getBody())->{$this->_getKeys()['one']});
        }

        return null;
    }

    /**
     * Deletes a specific server object by its id. The server must exist inside the project.
     *
     * @see https://docs.hetzner.cloud/#servers-delete-a-server
     *
     * @param  int  $serverId
     * @return \LKDev\HetznerCloud\Models\Actions\Action|null
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function deleteById(int $serverId): ?Action
    {
        $response = $this->httpClient->delete('servers/'.$serverId);
        if (! HetznerAPIClient::hasError($response)) {
            $payload = json_decode((string) $response->getBody());

            return Action::parse($payload->action);
        }

        return null;
    }

    /**
     * Creates a new server in a datacenter instead of in a location. Returns preliminary information about the server as well as an action that covers progress of creation.
     *
     * @see https://docs.hetzner.cloud/#resources-servers-post
     *
     * @param  string  $name
     * @param  \LKDev\HetznerCloud\Models\Servers\Types\ServerType  $serverType
     * @param  \LKDev\HetznerCloud\Models\Images\Image  $image
     * @param  \LKDev\HetznerCloud\Models\Locations\Location  $location
     * @param  \LKDev\HetznerCloud\Models\Datacenters\Datacenter  $datacenter
     * @param  array  $ssh_keys
     * @param  bool  $startAfterCreate
     * @param  string  $user_data
     * @param  array  $volumes
     * @param  bool  $automount
     * @param  array  $networks
     * @param  array  $labels
     * @param  array  $firewalls
     * @param  array  $public_net
     * @return APIResponse|null
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function createInDatacenter(
        string $name,
        ServerType $serverType,
        Image $image,
        ?Datacenter $datacenter = null,
        $ssh_keys = [],
        $startAfterCreate = true,
        $user_data = '',
        $volumes = [],
        $automount = false,
        $networks = [],
        array $labels = [],
        array $firewalls = [],
        ?array $public_net = null,
        ?int $placement_group = null
    ): ?APIResponse {
        $parameters = [
            'name' => $name,
            'server_type' => $serverType->id,
            'datacenter' => $datacenter == null ? null : $datacenter->id,
            'image' => $image->id,
            'start_after_create' => $startAfterCreate,
            'user_data' => $user_data,
            'ssh_keys' => $ssh_keys,
            'volumes' => $volumes,
            'automount' => $automount,
            'networks' => $networks,
        ];
        if (! empty($labels)) {
            $parameters['labels'] = $labels;
        }
        if (! empty($firewalls)) {
            $parameters['firewalls'] = $firewalls;
        }
        if ($public_net !== null) {
            $parameters['public_net'] = $public_net;
        }
        if ($placement_group != null) {
            $parameters['placement_group'] = $placement_group;
        }
        $response = $this->httpClient->post('servers', [
            'json' => $parameters,
        ]);

        if (! HetznerAPIClient::hasError($response)) {
            $payload = json_decode((string) $response->getBody());

            return APIResponse::create(array_merge([
                'action' => Action::parse($payload->action),
                'server' => Server::parse($payload->server),
                'next_actions' => collect($payload->next_actions)->map(function ($action) {
                    return Action::parse($action);
                })->toArray(),
            ], (property_exists($payload, 'root_password')) ? ['root_password' => $payload->root_password] : []
            ), $response->getHeaders());
        }

        return null;
    }

    /**
     * Creates a new server in a location instead of in a datacenter. Returns preliminary information about the server as well as an action that covers progress of creation.
     *
     * @see https://docs.hetzner.cloud/#resources-servers-post
     *
     * @param  string  $name
     * @param  ServerType  $serverType
     * @param  Image  $image
     * @param  Location|null  $location
     * @param  array  $ssh_keys
     * @param  bool  $startAfterCreate
     * @param  string  $user_data
     * @param  array  $volumes
     * @param  bool  $automount
     * @param  array  $networks
     * @param  array  $labels
     * @param  array  $firewalls
     * @return APIResponse|null
     *
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function createInLocation(string $name,
                                     ServerType $serverType,
                                     Image $image,
                                     ?Location $location = null,
                                     array $ssh_keys = [],
                                     bool $startAfterCreate = true,
                                     string $user_data = '',
                                     array $volumes = [],
                                     bool $automount = false,
                                     array $networks = [],
                                     array $labels = [],
                                     array $firewalls = [],
                                     ?array $public_net = null,
                                     ?int $placement_group = null
    ): ?APIResponse {
        $parameters = [
            'name' => $name,
            'server_type' => $serverType->id,
            'location' => $location == null ? null : $location->id,
            'image' => $image->id,
            'start_after_create' => $startAfterCreate,
            'user_data' => $user_data,
            'ssh_keys' => $ssh_keys,
            'volumes' => $volumes,
            'automount' => $automount,
            'networks' => $networks,
        ];
        if (! empty($labels)) {
            $parameters['labels'] = $labels;
        }
        if (! empty($firewalls)) {
            $parameters['firewalls'] = $firewalls;
        }
        if ($public_net !== null) {
            $parameters['public_net'] = $public_net;
        }
        if ($placement_group != null) {
            $parameters['placement_group'] = $placement_group;
        }
        $response = $this->httpClient->post('servers', [
            'json' => $parameters,
        ]);
        if (! HetznerAPIClient::hasError($response)) {
            $payload = json_decode((string) $response->getBody());

            return APIResponse::create(array_merge([
                'action' => Action::parse($payload->action),
                'server' => Server::parse($payload->server),
                'next_actions' => collect($payload->next_actions)->map(function ($action) {
                    return Action::parse($action);
                })->toArray(),
            ], (property_exists($payload, 'root_password')) ? ['root_password' => $payload->root_password] : []
            ), $response->getHeaders());
        }

        return null;
    }

    /**
     * @param  $input
     * @return $this
     */
    public function setAdditionalData($input)
    {
        $this->servers = collect($input)
            ->map(function ($server) {
                if ($server != null) {
                    return Server::parse($server);
                }

                return null;
            })
            ->toArray();

        return $this;
    }

    /**
     * @param  $input
     * @return static
     */
    public static function parse($input)
    {
        return (new self())->setAdditionalData($input);
    }

    /**
     * @return array
     */
    public function _getKeys(): array
    {
        return ['one' => 'server', 'many' => 'servers'];
    }
}
