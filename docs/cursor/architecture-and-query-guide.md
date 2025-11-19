# Lyre Architecture & Query Guide

This document complements the main README by digging into the moving parts that Lyre wires up for every model. It also documents the request/query patterns that the base `Repository`, `Controller`, `Resource`, and `Model` classes understand, and shows how we can use those conventions to query hierarchical data such as curriculum facets (courses ➜ subjects ➜ topics) without writing custom SQL.

## 1. Base Model & Traits

Lyre models extend `Lyre\Model`, which in turn brings in `BaseModelTrait`. A few important behaviors come for free:

| Feature | Description |
| --- | --- |
| Table prefixing | Any model that lives under the `Lyre\` namespace automatically prefixes its table with `config('lyre.table_prefix')`. Package tables stay isolated from the host app while app models remain untouched. |
| Fillable & metadata helpers | `BaseModelTrait` exposes helpers that `Resource::serializableColumns()` relies on to know which attributes can be returned, as well as hooks for `included`/`excluded` columns. |
| Relationship discovery | When you run `php artisan cache:relationships`, Lyre caches relationship metadata that the `Resource` layer can auto-load. |

## 2. Repository API

`Lyre\Repository` is the heart of Lyre’s querying system. Every generated repository simply extends it and immediately inherits a fluent API that supports method chaining and HTTP query-string equivalents.

### 2.1 Method chaining in PHP

```php
$posts = postRepository()
    ->with(['author', 'comments.replies'])
    ->columnFilters(['status' => 'published'])
    ->rangeFilters(['created_at' => [now()->subWeek(), now()]])
    ->relationFilters([
        'author' => ['column' => 'users.id', 'value' => auth()->id()],
    ])
    ->searchQuery(['search' => 'lyre'])
    ->orderBy('created_at', 'desc')
    ->paginate(25)
    ->all();
```

Every helper simply toggles internal flags. Once you call `all()` or `find()`, the repository builds a query with all the requested filters/order/relations.

### 2.2 Query-string equivalents

`Lyre\Controller::index()` and `Lyre\Repository::prepareQuery()` map HTTP query parameters to the same helpers. The table below lists the ones handled in `Repository::prepareQuery()` so far:

| Query string | Effect |
| --- | --- |
| `with=relation1,relation2` | Eager-load relationships as long as they exist in the model’s relationship metadata (detected via `cache:relationships`). |
| `paginate=true|false` | Globally toggle pagination for the request. |
| `per_page=25`, `page=3` | Standard pagination controls. |
| `latest=5` | Return only the most recent *n* entries. |
| `order=column,direction` | Override the default `ORDER_COLUMN` / `ORDER_DIRECTION`. Works with relationship columns via `relation.column`. |
| `relation=relation.path,value,...` | Filter by a related model’s primary/slug column. Nested paths are supported (e.g. `relation=facet.slug,curriculum`). |
| `relation_in=relation,val1,val2` | Filter where the related key is within the provided list. |
| `filter=column,value,...` | Apply column filters to the base table. |
| `range=column,min,max,...` | Apply `whereBetween` filters with automatic casting based on column type. |
| `search=term` (optional `search-relations=relation,column,...`) | Keyword search across serializable columns and optionally across relationships. |
| `startswith=ab` | Filter rows whose `NAME_COLUMN` starts with the value (uses `LIKE`/`ILIKE`). |
| `withcount=relation1,relation2` | Adds `relation_count` columns to the payload. |
| `wherenull=column1,column2` | Adds `orWhereNull` filters. |
| `doesnthave=relation1,relation2` | Ensures the listed relationships are empty. |
| `limit=10`, `offset=5` | Apply `limit`/`offset`. |
| `random=true` | Applies `inRandomOrder()`. |
| `first=true` | Tells the repository to return only the first record. |
| `unpaginated=true` | A shorthand to disable pagination.

These query parameters work consistently across every Lyre-powered endpoint because all controllers and repositories derive from the same base classes.

## 3. Controller lifecycle

`Lyre\Controller` is a thin, RESTful wrapper that delegates all heavy lifting to repositories and resources:

| Method | Notes |
| --- | --- |
| `index()` | Builds the repository query based on HTTP parameters (see table above) and returns a resource collection. Supports `scope` routes where a nested resource (e.g. `/courses/{course}/subjects`) is expected. |
| `store()` | Validates the request using the configured FormRequest class (`store-request` in the model config), auto-attaches scoped foreign keys, and calls `Repository::create()`. |
| `show()` | Uses `localAuthorize()` to fetch the model via repository and authorize the policy before returning the resource. |
| `update()` | Handles both single and bulk updates (comma-separated slugs) and routes through `Repository::update()`. |
| `destroy()` | Soft-deletes or deletes via the repository, again respecting policies.

Other notable behaviors:
- `globalAuthorize()` wires up policy checks on all REST verbs except `show`, `update`, and `destroy`, which are explicitly authorized via `localAuthorize()`.
- `getScopeCallback()` + `getScopedResource()` allow nested resource routes to automatically constrain queries to the owning model.
- Form requests can merge uploaded files because the controller re-instantiates FormRequest objects (`$modelRequestInstance->validateResolved()`) rather than relying on the container alone.

## 4. Resource serialization

`Lyre\Resource` extends Laravel’s `JsonResource` and makes responses dynamic:

- `serializableColumns()` loads fillable attributes, physical columns, `included`/`excluded` attributes from the model, and always appends `id`, `name`, `status` so clients can rely on them.
- `loadRelations()` inspects the model’s relationship map and only serializes relations requested via `with=` or configured via `loadResources()` in your resource class.
- `pivotRelations()` enables returning pivot payloads, controlled via `pivotResources()`.
- `columnMeta()` lets you register transformers (e.g., convert enums to human-readable labels) per attribute.
- `prepareCollection()` wraps paginated responses in `{ data, meta }` while leaving non-paginated responses as simple collections.

## 5. Hierarchical Facets and Query Patterns

Facets can now have parent-child relationships, enabling multi-tenant categorization structures. For example:
- **Standard Curriculum**: Course (root) → Subject (parent: Course) → Topic (parent: Subject)
- **Extended Curriculum**: Curriculum (root) → Learning Areas (parent: Curriculum) → Topics (parent: Learning Areas) → Subtopics (parent: Topics)

### 5.1 Facet Hierarchy Structure

- **Facets** can have `parent_id` to create facet hierarchies (e.g., Course facet → Subject facet → Topic facet)
- **FacetValues** belong to a specific facet and can have `parent_id` to create value hierarchies (e.g., "Mathematics" course → "Algebra" subject → "Linear Equations" topic)
- This dual hierarchy allows tenants to define custom categorization structures

### 5.2 Get Facet Hierarchy

```http
GET /api/facets/hierarchy
```

Returns the complete facet hierarchy tree for the current tenant, including:
- Facet names, slugs, and parent relationships
- Depth levels for each facet
- Nested children structure

Example response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Course",
      "slug": "course",
      "parent_id": null,
      "depth": 0,
      "children": [
        {
          "id": 2,
          "name": "Subject",
          "slug": "subject",
          "parent_id": 1,
          "depth": 1,
          "children": [
            {
              "id": 3,
              "name": "Topic",
              "slug": "topic",
              "parent_id": 2,
              "depth": 2
            }
          ]
        }
      ]
    }
  ]
}
```

### 5.3 Query Facet Values by Hierarchical Facets

#### Get root-level facet values (e.g., courses)

```http
GET /api/facetvalues?relation=facet.slug,<root-facet-slug>&filter=parent_id,null&with=children
```

- `relation=facet.slug,<root-facet-slug>` limits results to the root facet (e.g., `course` or `curriculum`)
- `filter=parent_id,null` ensures only top-level values are returned
- `with=children` eagerly loads child values

#### Get child facet values (e.g., subjects for a course)

```http
GET /api/facetvalues?relation=parent.slug,<parent-value-slug>&filter=facet.slug,<child-facet-slug>&with=children
```

- `relation=parent.slug,<parent-value-slug>` filters by parent facet value
- `filter=facet.slug,<child-facet-slug>` ensures results belong to the correct child facet type
- `with=children` eagerly loads grandchildren

#### Example: Get subjects for a course

```http
GET /api/facetvalues?relation=parent.slug,mathematics-101&filter=facet.slug,subject&with=children
```

This returns all subject facet values that are children of the "mathematics-101" course value.

### 5.4 Multi-Tenant Categorization

Each tenant can define their own facet hierarchy:
- **Tenant A**: Course → Subject → Topic
- **Tenant B**: Curriculum → Learning Areas → Topics → Subtopics

The UI dynamically uses facet names from the hierarchy, so labels adapt automatically to each tenant's structure.

> **Tip:** Use `GET /api/facets/hierarchy` to fetch the hierarchy once and cache it in your frontend. Then use the facet slugs dynamically in all queries.

## 6. Tips & Best Practices

- **Bulk updates:** Every `update` endpoint accepts comma-separated IDs (e.g. `PUT /posts/1,2,3`). The controller automatically switches to the `bulkUpdate` authorization ability.
- **Scopes & nested routes:** If you add routes like `Route::apiResource('courses.subjects', CourseSubjectController::class);`, Lyre’s controller logic will scope repository queries to the owning course automatically.
- **Facet-driven categorization:** Instead of hard-coding foreign keys, use facets for any user-defined taxonomy. The combination of `relation` filters + `with` loading gives you flexible APIs without new tables.
- **Extending resources:** Override `columnMeta()`, `loadResources()`, or `pivotResources()` in concrete resource classes to tweak what’s exposed.

For day-to-day usage examples (installing Lyre, generating models, etc.), keep reading the main [README](../README.md). For deeper inspection of a specific layer, open the source files referenced above—they’re intentionally small and composable.
