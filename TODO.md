# TODO

- [x] Add route PATCH /api/medical-records/{id}/sign to doctor-scoped group
- [x] Create SignMedicalRecordRequest (authorization + route id validation)
- [x] Create MedicalRecordSignService (transaction + lockForUpdate + finalize fields)
- [x] Extend MedicalRecordController with sign() method using DI
- [x] Add MedicalRecordSignTest feature tests

