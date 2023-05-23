# [READONLY-SUBSPLIT] {{ packageName }}


![Continuous Integration](https://github.com/php-api-clients/{{ packageName }}/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/api-clients/{{ packageName }}/v/stable.png)](https://packagist.org/packages/api-clients/{{ packageName }})
[![Total Downloads](https://poser.pugx.org/api-clients/{{ packageName }}/downloads.png)](https://packagist.org/packages/api-clients/{{ packageName }})
[![Code Coverage](https://scrutinizer-ci.com/g/php-api-clients/{{ packageName }}/badges/coverage.png?b=={{ branch }})](https://scrutinizer-ci.com/g/php-api-clients/{{ packageName }}/?branch={{ branch }})
[![License](https://poser.pugx.org/api-clients/{{ packageName }}/license.png)](https://packagist.org/packages/api-clients/{{ packageName }})

Non-Blocking first {{ fullName }} client, this is a read only sub split, see [`github-root`](https://github.com/php-api-clients/github-root).

{% if client.configuration.entryPoints.call == true or client.configuration.entryPoints.operations == true %}
## Supported operations

{% for operation in client.operations %}

### {{ operation.operationId }}

{{ operation.summary }}

{% if client.configuration.entryPoints.call == true %}
Using the `call` method:
```php
$client->call('{{ operation.matchMethod }} {{ operation.path }}'{% if operation.parameters|length > 0 %}, [
{% for parameter in operation.parameters %}        '{{ parameter.targetName }}' => {% if parameter.type == 'string' %}'{% endif %}{{ parameter.example.raw }}{% if parameter.type == 'string' %}'{% endif %},
{% endfor %}]{% endif %});
```
{% endif %}

{% if client.configuration.entryPoints.operations == true %}
Operations method:
```php
$client->operations()->{{ operation.groupCamel }}()->{{ operation.nameCamel }}({% if operation.parameters|length > 0 %}
{% for parameter in operation.parameters %}        {{ parameter.targetName }}: {% if parameter.type == 'string' %}'{% endif %}{{ parameter.example.raw }}{% if parameter.type == 'string' %}'{% endif %},
{% endfor %}{% endif %});
```
{% endif %}

{% if operation.externalDocs != null %}
You can find more about this operation over at the [{{ operation.externalDocs.description }}]({{ operation.externalDocs.url }}).
{% endif %}

{% endfor %}
{% endif %}
