# DynamicForms Module

**Block:** 7.2 — DynamicForms Backend Foundation  
**Status:** Active

---

## Purpose

Tenant-owned, schema-driven form management with immutable version snapshots and validated submissions.

DynamicForms is the canonical reference module for Core Platform. It demonstrates correct usage of every platform convention.

---

## Module Structure

```
App\Core\DynamicForms\
  Enums\
    FormStatus.php
  Http\
    Controllers\
      FormController.php
      FormVersionController.php
      FormSubmissionController.php
    Requests\
      StoreFormRequest.php
      UpdateFormRequest.php
      PublishFormRequest.php
      StoreFormVersionRequest.php
      SubmitFormRequest.php
    Resources\
      FormResource.php
      FormVersionResource.php
      FormSubmissionResource.php
  Models\
    Form.php
    FormVersion.php
    FormSubmission.php
  Policies\
    FormPolicy.php
    FormVersionPolicy.php
    FormSubmissionPolicy.php
  Providers\
    DynamicFormsServiceProvider.php
  Routes\
    api.php
  Validation\
    FormSchemaValidator.php
    FormSubmissionValidator.php
```

---

## API Routes

All routes are tenant-safe (TenantRouteRegistrar).

```
GET    /forms
POST   /forms
GET    /forms/{form}
PATCH  /forms/{form}
POST   /forms/{form}/publish

GET    /forms/{form}/versions
POST   /forms/{form}/versions
GET    /form-versions/{version}

POST   /forms/{form}/submit
GET    /forms/{form}/submissions
GET    /submissions/{submission}
```

---

## Architecture References

- [overview.md](../../../../docs/features/dynamic-forms/overview.md)
- [domain-model.md](../../../../docs/features/dynamic-forms/domain-model.md)
- [database-design.md](../../../../docs/features/dynamic-forms/database-design.md)
- [schema-contract.md](../../../../docs/features/dynamic-forms/schema-contract.md)
- [validation-strategy.md](../../../../docs/features/dynamic-forms/validation-strategy.md)
- [authorization-rules.md](../../../../docs/features/dynamic-forms/authorization-rules.md)
