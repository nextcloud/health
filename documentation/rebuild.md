Goals:

1. No CES
	* Performance in big instances
	* easier bug tracking

2. Make API for each module
	* separate store and API
	* Data goes to the store!

3. Not that much magic that struggles myself

4. FE: Use general components
	* DataTable
		* grouping by days
	* Add item modal
	* Chart

5. Everything is updatable
	* Version 1 = last version 0.x
	* Version 1 with clean db definitions

6. Routing for modules
> **tld/health/[module]/[type]/[action]/*params***
>
> tld/health/weight/dataset/person/{personId} GET -> find
> tld/health/weight/dataset/person/{personId} POST -> create
> tld/health/weight/dataset/{id} DELETE -> remove
> tld/health/weight/dataset/{id} PUT -> update
