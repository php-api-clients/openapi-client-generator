OPENAPI_GENERATOR=$(DOCKER_RUN) php ./vendor/bin/openapi-generator

generate-example-clients: generate-example-client-one generate-example-client-subsplit generate-example-client-miele

generate-example-client-one:
	$(OPENAPI_GENERATOR) ./example/openapi-client-one.php

generate-example-client-subsplit:
	$(OPENAPI_GENERATOR) ./example/openapi-client-subsplit.php

generate-example-client-miele:
	$(OPENAPI_GENERATOR) ./example/openapi-client-miele.php

generate-test-client:
	$(OPENAPI_GENERATOR) ./tests/openapi-client-petstore.php

generate-packages:
	$(OPENAPI_GENERATOR) ./example/client-gitub-one.php
