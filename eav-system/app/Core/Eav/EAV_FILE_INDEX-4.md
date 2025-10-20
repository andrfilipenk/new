# EAV Library - File Index

## Quick Reference

This document provides a complete index of all EAV library files and their purposes.

---

## Core Library Files (24 PHP files)

### Exception Layer (5 files)
Location: `app/Core/Eav/Exception/`

| File | Purpose | Extends |
|------|---------|---------|
| EavException.php | Base exception for all EAV errors | Core\Exception\BaseException |
| EntityException.php | Entity-specific errors (not found, etc.) | EavException |
| ConfigurationException.php | Configuration and setup errors | EavException |
| StorageException.php | Database and persistence errors | EavException |
| SynchronizationException.php | Schema sync errors | EavException |

### Model Layer (4 files)
Location: `app/Core/Eav/Model/`

| File | Purpose | Key Methods |
|------|---------|-------------|
| Attribute.php | Attribute definition and metadata | getCode(), getBackendType(), isRequired() |
| AttributeCollection.php | Collection of attributes | add(), get(), groupByBackendType() |
| EntityType.php | Entity type definition | getCode(), getAttributes(), getAttribute() |
| Entity.php | Entity instance with dirty tracking | get(), set(), validate(), isDirty() |

### Configuration Layer (2 files)
Location: `app/Core/Eav/Config/`

| File | Purpose | Key Methods |
|------|---------|-------------|
| ConfigLoader.php | Loads entity configurations from files | load(), loadAll(), exists() |
| EntityTypeRegistry.php | Runtime cache of entity types | get(), has(), register(), all() |

### Schema Management (4 files)
Location: `app/Core/Eav/Schema/`

| File | Purpose | Key Methods |
|------|---------|-------------|
| TableDefinition.php | Table metadata representation | getTableName(), getColumns(), getIndexes() |
| StructureBuilder.php | Generates Blueprint definitions | buildEntityTable(), buildAttributeTable(), buildValueTable() |
| MigrationGenerator.php | Creates migration files | generateBaseMigration(), generateEntityTypeMigration() |
| SchemaManager.php | Schema orchestration | initialize(), synchronize(), needsSynchronization() |

### Storage Layer (3 files)
Location: `app/Core/Eav/Storage/`

| File | Purpose | Key Methods |
|------|---------|-------------|
| StorageStrategyInterface.php | Storage contract | loadValues(), saveValues(), deleteValues() |
| ValueTransformer.php | Type conversion | toDatabase(), fromDatabase(), validate() |
| EavTableStorage.php | EAV pattern implementation | loadValues(), saveValues(), loadMultiple() |

### Manager Layer (3 files)
Location: `app/Core/Eav/Manager/`

| File | Purpose | Key Methods |
|------|---------|-------------|
| AttributeMetadataManager.php | Metadata synchronization | syncAttributes(), getAttributeId(), loadAttributes() |
| ValueManager.php | Value coordination | loadValues(), saveValues(), loadMultiple() |
| EntityManager.php | Entity lifecycle (CRUD) | create(), load(), save(), delete(), loadMultiple() |

### Repository Layer (2 files)
Location: `app/Core/Eav/Repository/`

| File | Purpose | Key Methods |
|------|---------|-------------|
| EntityRepository.php | Entity queries | findById(), findByAttribute(), search(), paginate() |
| AttributeRepository.php | Attribute queries | findByCode(), findSearchable(), findFilterable() |

### Service Provider (1 file)
Location: `app/Core/Eav/Provider/`

| File | Purpose | Services Registered |
|------|---------|---------------------|
| EavServiceProvider.php | DI registration | 12 services (see below) |

---

## Configuration Files (2 files)
Location: `config/eav/`

| File | Entity Type | Attributes |
|------|-------------|------------|
| product.php | Product | name, sku, description, price, quantity, is_active, created_date |
| customer.php | Customer | first_name, last_name, email, phone, date_of_birth, address, is_verified |

---

## Migration Files (1 file)
Location: `migrations/`

| File | Purpose | Tables Created |
|------|---------|----------------|
| 2025_01_15_000000_create_eav_base_structure.php | Base EAV schema | 7 tables (entity_type, attribute, 5 value tables) |

---

## Examples (1 file)
Location: `examples/`

| File | Lines | Purpose |
|------|-------|---------|
| eav_example.php | 167 | Complete workflow demonstration |

Demonstrates:
- Schema initialization
- Entity type synchronization
- Entity creation, loading, updating, deleting
- Repository queries
- Search functionality
- Pagination

---

## Tests (1 file)
Location: `tests/Core/Eav/`

| File | Lines | Test Cases |
|------|-------|------------|
| EavIntegrationTest.php | 433 | 10 integration tests |

Tests cover:
1. Schema initialization
2. Entity type synchronization
3. Entity creation
4. Entity loading
5. Entity update
6. Multiple entity operations
7. Repository queries
8. Validation
9. Dirty tracking
10. Entity deletion

---

## Documentation (4 files)

| File | Lines | Purpose |
|------|-------|---------|
| app/Core/Eav/README.md | 440 | Complete API documentation |
| app/Core/Eav/CHANGELOG.md | 184 | Version history and changes |
| QUICKSTART_EAV.md | 216 | Quick start guide |
| IMPLEMENTATION_SUMMARY.md | 371 | Implementation details |

---

## Modified Framework Files (1 file)

| File | Change | Line |
|------|--------|------|
| bootstrap.php | Added EAV service provider registration | 42 |

---

## Services Registered in DI Container

| Service Name | Class | Type | Purpose |
|--------------|-------|------|---------|
| eav.config_loader | ConfigLoader | Singleton | Load entity configurations |
| eav.registry | EntityTypeRegistry | Singleton | Runtime entity type cache |
| eav.structure_builder | StructureBuilder | Singleton | Generate table definitions |
| eav.migration_generator | MigrationGenerator | Singleton | Create migration files |
| eav.schema_manager | SchemaManager | Singleton | Schema orchestration |
| eav.value_transformer | ValueTransformer | Singleton | Type conversion |
| eav.storage.eav | EavTableStorage | Singleton | EAV storage implementation |
| eav.attribute_metadata_manager | AttributeMetadataManager | Singleton | Metadata sync |
| eav.value_manager | ValueManager | Singleton | Value coordination |
| eav.entity_manager | EntityManager | Singleton | Entity lifecycle |
| eav.entity_repository | EntityRepository | Factory | Entity queries |
| eav.attribute_repository | AttributeRepository | Singleton | Attribute queries |

---

## Database Tables

### Core EAV Tables (7)

1. **eav_entity_type** - Entity type registry
2. **eav_attribute** - Attribute metadata
3. **eav_value_varchar** - String values (up to 255 chars)
4. **eav_value_int** - Integer values
5. **eav_value_decimal** - Decimal values (12,4 precision)
6. **eav_value_datetime** - Date/time values
7. **eav_value_text** - Long text values

### Entity Tables (Dynamic)

Created per entity type:
- **eav_entity_product** - Product entities
- **eav_entity_customer** - Customer entities
- Additional tables created as needed

---

## Directory Structure

```
app/Core/Eav/
├── CHANGELOG.md
├── README.md
├── Config/
│   ├── ConfigLoader.php
│   └── EntityTypeRegistry.php
├── Exception/
│   ├── ConfigurationException.php
│   ├── EavException.php
│   ├── EntityException.php
│   ├── StorageException.php
│   └── SynchronizationException.php
├── Manager/
│   ├── AttributeMetadataManager.php
│   ├── EntityManager.php
│   └── ValueManager.php
├── Model/
│   ├── Attribute.php
│   ├── AttributeCollection.php
│   ├── Entity.php
│   └── EntityType.php
├── Provider/
│   └── EavServiceProvider.php
├── Repository/
│   ├── AttributeRepository.php
│   └── EntityRepository.php
├── Schema/
│   ├── MigrationGenerator.php
│   ├── SchemaManager.php
│   ├── StructureBuilder.php
│   └── TableDefinition.php
└── Storage/
    ├── EavTableStorage.php
    ├── StorageStrategyInterface.php
    └── ValueTransformer.php

config/eav/
├── customer.php
└── product.php

migrations/
└── 2025_01_15_000000_create_eav_base_structure.php

examples/
└── eav_example.php

tests/Core/Eav/
└── EavIntegrationTest.php

Documentation:
├── QUICKSTART_EAV.md
└── IMPLEMENTATION_SUMMARY.md
```

---

## File Dependencies

### High-Level Dependency Graph

```
ConfigLoader → EntityTypeRegistry → EntityType → Entity
                                   ↓
                              AttributeCollection → Attribute

SchemaManager → StructureBuilder → Blueprint (framework)
             → MigrationGenerator
             → AttributeMetadataManager → Database

EntityManager → ValueManager → EavTableStorage → Database
             → EntityTypeRegistry      → ValueTransformer

EntityRepository → EntityManager → Database
AttributeRepository → Database
```

---

## Usage Flow

1. **Bootstrap** → Load EavServiceProvider
2. **Initialize** → SchemaManager.initialize()
3. **Configure** → Create config/eav/{entity}.php
4. **Synchronize** → SchemaManager.synchronize()
5. **Use** → EntityManager, Repository for operations

---

## Total Statistics

- **PHP Files**: 24 (core library)
- **Configuration Files**: 2
- **Migration Files**: 1
- **Example Files**: 1
- **Test Files**: 1
- **Documentation Files**: 4
- **Total Lines of Code**: ~7,000+
- **Database Tables**: 7 core + 2 entity (extensible)
- **Services Registered**: 12
- **Test Cases**: 10

---

## Quick Navigation

- **Getting Started**: See `QUICKSTART_EAV.md`
- **API Reference**: See `app/Core/Eav/README.md`
- **Examples**: See `examples/eav_example.php`
- **Tests**: See `tests/Core/Eav/EavIntegrationTest.php`
- **Changes**: See `app/Core/Eav/CHANGELOG.md`
- **Summary**: See `IMPLEMENTATION_SUMMARY.md`
