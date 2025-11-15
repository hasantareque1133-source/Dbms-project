# Normalization Case Study: Community Arts Fair Catering Orders

**Course:** CSE 2221 — Database Management Systems  
**Course Outcome:** CO3  
**Submission Format:** Printed hard copy (due 24 November 2025)  
**Guidelines Addressed:** Clear responses, unique scenario, labeled artifacts

---

## 1. Scenario Overview
The Lakeview Community Arts Fair collects catering requests from student clubs. A single paper form captures order logistics along with a list of menu items and their per-item costs. Because the form stores repeating menu details inside one row, the resulting table contains redundancy, partial dependencies, and transitive dependencies.

---

## 2. Unnormalized Table (UNF)
**Table Name:** `FestivalCateringOrder_UNF`  
**Attributes (7 columns):**
1. `OrderCode` — unique order identifier provided by the fair committee.  
2. `EventDate` — date of the event.  
3. `OrganizerEmail` — primary contact for the student club.  
4. `VenueZone` — campus zone assigned for the food truck.  
5. `ZoneSupervisorPhone` — phone number of the campus facilities supervisor responsible for that zone.  
6. `PackageTier` — pricing tier (Silver, Gold, etc.).  
7. `MenuItemDetails` — repeating group storing triples (`DishName | DishCategory | UnitPrice`).

**Sample Rows (5 entries):**

| OrderCode | EventDate | OrganizerEmail | VenueZone | ZoneSupervisorPhone | PackageTier | MenuItemDetails |
| --- | --- | --- | --- | --- | --- | --- |
| ORD-101 | 2025-03-12 | eco.club@campus.edu | North Quad | 555-8144 | Silver | Veg Wrap\|Starter\|5 ; Solar Salad\|Main\|11 ; Citrus Cake\|Dessert\|7 |
| ORD-102 | 2025-03-15 | dance.circle@campus.edu | East Lawn | 555-2279 | Gold | Garden Rolls\|Starter\|6 ; Fire Grill\|Main\|13 |
| ORD-103 | 2025-03-19 | robotics.hub@campus.edu | North Quad | 555-8144 | Gold | Veg Wrap\|Starter\|5 ; Kinetic Curry\|Main\|12 ; Circuit Sundae\|Dessert\|8 |
| ORD-104 | 2025-03-19 | drama.club@campus.edu | South Lawn | 555-9902 | Silver | Lantern Soup\|Starter\|4 ; Stage Stew\|Main\|10 ; Finale Tart\|Dessert\|6 |
| ORD-105 | 2025-03-22 | chess.guild@campus.edu | East Lawn | 555-2279 | Bronze | Garden Rolls\|Starter\|6 |

**Observations:**  
- Repeating menu details violate 1NF.  
- Partial dependency: `EventDate`, `OrganizerEmail`, `VenueZone`, and `PackageTier` depend only on `OrderCode`, not on individual menu items.  
- Transitive dependency: `VenueZone -> ZoneSupervisorPhone`.

---

## 3. Functional Dependencies (UNF Context)
- **FD1:** `OrderCode -> EventDate, OrganizerEmail, VenueZone, PackageTier`  
- **FD2:** `VenueZone -> ZoneSupervisorPhone`  
- **FD3:** `DishName -> DishCategory, UnitPrice` (captured inside `MenuItemDetails`, causing redundancy)

---

## 4. Step-by-Step Normalization

### Step 1 — First Normal Form (1NF)
**Goal:** Remove repeating groups by creating one row per menu item.  
**Resulting Table:** `FestivalOrderLine_1NF`

| Column | Notes |
| --- | --- |
| `OrderCode` (part of PK) | |
| `DishName` (part of PK) | extracted from `MenuItemDetails` |
| `EventDate` | |
| `OrganizerEmail` | |
| `VenueZone` | |
| `ZoneSupervisorPhone` | depends on `VenueZone` |
| `PackageTier` | |
| `DishCategory` | from repeating group |
| `UnitPrice` | from repeating group |

**Primary Key:** (`OrderCode`, `DishName`)  
**Remaining Functional Dependencies:**  
- `OrderCode -> EventDate, OrganizerEmail, VenueZone, PackageTier` (partial dependency remains)  
- `VenueZone -> ZoneSupervisorPhone` (transitive)  
- `DishName -> DishCategory, UnitPrice`

### Step 2 — Second Normal Form (2NF)
**Goal:** Remove partial dependencies on part of the composite key.  
**Transformations:** split header data and menu catalog.

1. `FestivalOrderHeader(OrderCode PK, EventDate, OrganizerEmail, VenueZone, ZoneSupervisorPhone, PackageTier)`  
2. `FestivalOrderDish(OrderCode PK/FK, DishName PK, DishCategory, UnitPrice)`

**Updated FDs:**  
- Header: `OrderCode -> EventDate, OrganizerEmail, VenueZone, ZoneSupervisorPhone, PackageTier`  
- Dish line: `DishName -> DishCategory, UnitPrice` (still redundant across orders)  
- `VenueZone -> ZoneSupervisorPhone` (still transitive within header)

### Step 3 — Third Normal Form (3NF)
**Goal:** Remove transitive dependencies (`VenueZone -> ZoneSupervisorPhone`) and dish metadata repetition.

**Final Tables:**
1. `FestivalOrderHeader(<u>OrderCode</u>, EventDate, OrganizerEmail, VenueZone*, PackageTier)`  
2. `VenueZoneDirectory(<u>VenueZone</u>, ZoneSupervisorPhone)`  
3. `MenuCatalog(<u>DishName</u>, DishCategory, UnitPrice)`  
4. `FestivalOrderItem(<u>OrderCode</u>*, <u>DishName</u>*)`

**Resulting Functional Dependencies:**  
- `OrderCode -> EventDate, OrganizerEmail, VenueZone, PackageTier` (stored in `FestivalOrderHeader`).  
- `VenueZone -> ZoneSupervisorPhone` (captured in `VenueZoneDirectory`).  
- `DishName -> DishCategory, UnitPrice` (captured in `MenuCatalog`).  
- `OrderCode, DishName -> (association only)` in `FestivalOrderItem`, so every determinant now covers whole keys with no transitive paths.

---

## 5. Final Schema Summary (PK underlined, FK marked with *)
1. `FestivalOrderHeader(<u>OrderCode</u>, EventDate, OrganizerEmail, VenueZone*, PackageTier)`  
2. `VenueZoneDirectory(<u>VenueZone</u>, ZoneSupervisorPhone)`  
3. `MenuCatalog(<u>DishName</u>, DishCategory, UnitPrice)`  
4. `FestivalOrderItem(<u>OrderCode</u>*, <u>DishName</u>*)`

---

## 6. Normalization Benefits
The new design removes repeating menu details and ensures each fact lives in exactly one table. Header changes (date, organizer) now update once per order, menu prices live in `MenuCatalog`, and supervisor phones follow their zone rather than every order. This reduces redundancy, eliminates update anomalies, and provides clear primary/foreign key relationships ready for enforcement inside a relational database.

---

*Prepared by: [Your Name], Lakeview Community Arts Fair Scenario*

