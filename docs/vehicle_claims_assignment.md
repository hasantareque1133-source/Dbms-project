# Vehicle Claims Management System Database Design

## Assignment Context
- Institution: Premier University, Dept. of CSE • Spring 2025 • Course CSE 2221 (DBMS), CO3 • Total marks 10
- Case study: Red Insurance Company requires a claims database handling policies, insured vehicles, claims, accidents, drivers, witnesses, quotes, and other vehicles.
- Goal: Deliver a normalized relational design plus justification, ERD, relational schema, and discussion of engineering complexity considerations.

## 1. Why a Relational Database Fits Best
- **Data integrity & ACID**: Claims, accidents, and quotes drive financial payouts and must stay consistent; relational databases guarantee atomic multi-table updates and strong referential integrity.
- **Structured relationships**: Stakeholders already think in terms of policies, claims, witnesses, etc.; relational schemas map directly to these well-defined entities and constraints.
- **Query flexibility**: Regulators, auditors, and adjusters need ad-hoc joins (e.g., all accidents with no witnesses yet); SQL excels at such set-based queries without extra tooling.
- **Normalization & redundancy control**: The claims form contains repeating sections (witnesses, quotes); relational normalization eliminates duplicate facts, simplifying updates.
- **Maturity & oversight**: Enterprise insurers align with established standards (IFRS 17, solvency reporting) that expect relational storage, easing integration and compliance.

## 2. Identified Entities and Attribute Catalogue
| Entity | Key Attributes | Supporting Attributes | Notes |
| --- | --- | --- | --- |
| **Policy** | PolicyNumber (PK) | ExternalCustomerId, ProductType, Status | Stores only identifiers required inside the claims DB; other policy data remains in upstream systems. |
| **InsuredVehicle** | VehicleID (PK), RegistrationPlate (unique) | PolicyNumber (FK), VINHash, MakeModel, Year | Captures the vehicle tied to the claim; `PolicyNumber` enforces that each stored vehicle belongs to a known policy. |
| **Claim** | ClaimID (PK), ClaimNumber (business identifier, non-unique) | ClaimDate, ClaimAmount, ClaimDescription, AtFaultPartyFlag, PolicyNumber (FK), VehicleID (FK), AccidentID (FK) | One claim exists per accident; ClaimNumber from forms is retained even though it is not unique. |
| **Accident** | AccidentID (PK) | AccidentDate, AccidentTime, StreetName, CrossStreet, WetRoadFlag, HeadlightsOnFlag, ParkedFlag, ReportedToPoliceFlag, Description | Contains event-specific facts; linked 1:1 with Claim. |
| **Driver** | DriverID (PK) | FullName, BirthDate, Address, PhoneNumber, RelationshipToInsured, LicenceNumber, LicenceStatus, LicenceType, LicenceIssueDate | Optional per accident (null when the vehicle was parked). |
| **AccidentDriver** | AccidentID (FK), DriverID (FK) | Role (“Insured Driver”, “Other”), PresentFlag | Keeps driver participation normalized; supports historical reuse of the same driver profile across accidents. |
| **DriverTest** | TestID (PK) | AccidentID (FK), TestType (Breathalyzer/Drug), AdministeredFlag, Result, PerformedBy, Notes | Captures regulatory test outcomes without bloating the accident record. |
| **OtherVehicle** | OtherVehicleID (PK), RegistrationPlate | InsurerName, MakeModel | Represents vehicles not insured by Red that may appear in multiple accidents. |
| **AccidentOtherVehicle** | AccidentID (FK), OtherVehicleID (FK) | PassengerCount, DamageSummary | Associative entity that keeps passenger counts per accident per other vehicle. |
| **Witness** | WitnessID (PK) | FullName, Address, PhoneNumber, PreferredContactMethod | Witness data can relate to multiple accidents. |
| **AccidentWitness** | AccidentID (FK), WitnessID (FK) | Statement, ContactedFlag | Resolves the many-to-many relationship between accidents and witnesses. |
| **Quote** | QuoteID (PK) | ClaimID (FK), QuoteSequence (1-3+), RepairerName, RepairerAddress, TotalRepairCost, Currency, QuoteDate | Stores the mandated repair quotes per claim. |
| **AdjustmentNote** (optional, supports workflows) | NoteID (PK) | ClaimID (FK), CreatedBy, CreatedOn, NoteType, NoteText | Enables richer auditing without overloading Claim. |

## 3. Conceptual ER Diagram (Crow's Foot)
- Diagram source (Mermaid ER): `docs/vehicle_claims_erd.mmd`
- Key relationships (crow's foot cardinalities described):
  - One `Policy` covers many `InsuredVehicle` rows; each insured vehicle participates in zero or more `Claim` records.
  - Each `Claim` documents exactly one `Accident` (1:1), while an `Accident` may involve zero or one `Driver` via `AccidentDriver` when the vehicle was moving.
  - `Accident` to `OtherVehicle` is M:N resolved by `AccidentOtherVehicle`; same approach for `Accident` to `Witness`.
  - Each `Claim` requires three or more `Quote` entries.
  - `DriverTest` hangs off `Accident` to describe breathalyzer/drug test state.

Rendering tip: paste the Mermaid definition into any Mermaid-compatible viewer (e.g., VS Code, Obsidian) to obtain a crows-foot ERD printable at ≥9 pt font.

## 4. Normalization Walkthrough
1. **Unnormalized Form (UNF)**: The paper claim form mixes single-valued data with repeating groups (multiple quotes, witnesses, other vehicles) and optional driver/test sections within one document.
2. **First Normal Form (1NF)**: Split repeating groups into separate tables (`Quote`, `Witness`, `OtherVehicle`, etc.) so every field holds atomic values. Introduce surrogate keys (`ClaimID`, `AccidentID`) because handwritten ClaimNum is not unique.
3. **Second Normal Form (2NF)**: Remove partial dependencies on composite keys in associative entities by isolating master data. Example: witness contact info now lives in `Witness`, while `AccidentWitness` only stores intersection facts. Passenger counts move into `AccidentOtherVehicle` so they depend on the full (Accident, OtherVehicle) key.
4. **Third Normal Form (3NF)**: Eliminate transitive dependencies. Policy data stays in `Policy`; vehicle details in `InsuredVehicle` to avoid policy attributes depending on claim keys. Driver licence metadata remains inside `Driver`, while test outcomes are separated into `DriverTest` so results don’t depend on non-key driver attributes.

This progression ensures every non-key attribute depends on “the key, the whole key, and nothing but the key,” supporting update, insert, and delete integrity.

## 5. Relational Schema (PK underlined, FK marked with *)
- Policy(__PolicyNumber__)
- InsuredVehicle(__VehicleID__, RegistrationPlate, PolicyNumber*)
- Claim(__ClaimID__, ClaimNumber, ClaimDate, ClaimAmount, ClaimDescription, AtFaultPartyFlag, PolicyNumber*, VehicleID*, AccidentID*)
- Accident(__AccidentID__, AccidentDate, AccidentTime, StreetName, CrossStreet, WetRoadFlag, HeadlightsOnFlag, ParkedFlag, ReportedToPoliceFlag, Description)
- AccidentDriver(__AccidentID__, __DriverID__, Role, PresentFlag)
- Driver(__DriverID__, FullName, BirthDate, Address, PhoneNumber, RelationshipToInsured, LicenceNumber, LicenceStatus, LicenceType, LicenceIssueDate)
- DriverTest(__TestID__, AccidentID*, TestType, AdministeredFlag, Result, PerformedBy, Notes)
- OtherVehicle(__OtherVehicleID__, RegistrationPlate, MakeModel, InsurerName)
- AccidentOtherVehicle(__AccidentID__, __OtherVehicleID__, PassengerCount, DamageSummary)
- Witness(__WitnessID__, FullName, Address, PhoneNumber, PreferredContactMethod)
- AccidentWitness(__AccidentID__, __WitnessID__, Statement, ContactedFlag)
- Quote(__QuoteID__, ClaimID*, QuoteSequence, RepairerName, RepairerAddress, TotalRepairCost, Currency, QuoteDate)
- AdjustmentNote(__NoteID__, ClaimID*, CreatedBy, CreatedOn, NoteType, NoteText)

Referential rules mirror the ERD (e.g., `AccidentID` in `Claim` references `Accident`, `AccidentDriver` enforces cascading deletes from both parents, etc.).

## 6. Design Choices & Integrity Constraints
- **Surrogate keys**: Introduced `ClaimID`, `AccidentID`, etc., to avoid collisions and to cleanly support foreign keys despite non-unique claim numbers.
- **Optional driver linkage**: `ParkedFlag` in `Accident` plus nullable `AccidentDriver` rows let the design capture both parked and driven cases without dummy driver values.
- **Auditing**: `AdjustmentNote` (optional) provides extensibility for workflow without affecting core transactional entities.
- **Mandatory quotes**: Enforce `CHECK (QuoteSequence BETWEEN 1 AND 3)` plus `DEFERRABLE INITIALLY DEFERRED` trigger or stored procedure to ensure at least three quotes exist before claim approval.
- **Witness privacy**: Storing witnesses separately supports GDPR/PII retention policies and reuse in multiple accidents they observed.

## 7. Responses to Complex Problem-Solving Prompts
- **(a) In-depth engineering knowledge?** Yes. Designing 3NF schemas, enforcing integrity, and modelling optional sections require database theory plus domain expertise in insurance workflows.
- **(b) Conflicting technical/engineering issues?** Yes. The schema must balance regulatory reporting (needs detailed audit trails) with operational efficiency (fast claim intake), often leading to conflicting indexing and retention strategies.
- **(c) Well-known vs. abstract thinking?** Partially known. Core claim-handling patterns exist, but tailoring them to specific Red Insurance rules (e.g., parked vehicle logic, non-unique claim numbers) demands abstraction and careful analysis.
- **(d) Infrequently encountered issues?** Yes. Non-unique claim numbers, optional drivers, and variable regulatory tests are less common edge cases that complicate generic claim schemas.
- **(e) Standards & codes of practice?** Definitely. Insurance databases must comply with standards like IFRS 17, PCI DSS (if payments captured), and local data-protection acts, influencing data types and retention policies.
- **(f) Stakeholders with conflicting requirements?** Yes. Claims adjusters, actuaries, compliance officers, and IT security all need different slices of the same data, which the relational schema must reconcile.
- **(g) Interdependence between sub-problems?** Strongly. Policy-vehicle-claim linkages, quote requirements, and witness/test workflows are interdependent; violating one constraint (e.g., missing vehicle) breaks downstream processes.

## 8. Documentation & Next Steps
- Files delivered: `docs/vehicle_claims_assignment.md` (this report) and `docs/vehicle_claims_erd.mmd` (Mermaid ERD definition).
- Recommended follow-ups: generate a physical ER diagram (PDF) from the Mermaid file, review with stakeholders, then create DDL scripts based on the relational schema.
