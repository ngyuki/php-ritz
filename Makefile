UID := $(shell id -u)

shell:
	docker-compose run --rm -u $(UID) app sh

qa:
	docker-compose run --rm -u $(UID) -T app composer qa
