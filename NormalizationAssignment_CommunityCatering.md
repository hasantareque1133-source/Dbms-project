# Normalization Case Study: Community Arts Fair Catering Orders

**Course:** CSE 2221 - Database Management Systems  
**Outcome:** CO3 | **Submission:** Printed hard copy by 24 November 2025  
**Scenario Owner:** Lakeview Community Arts Fair (unique topic selected)

---

## 1. Scenario Overview
Student clubs submit a single catering form that lists event logistics plus multiple dishes. The form stores every dish (name, category, price) inside one cell, which introduces redundancy, anomalies, and mixed dependencies. The goal is to design the original unnormalized structure and normalize it to 3NF while showing every step with labeled artifacts.

---

## 2. Unnormalized Table (UNF) — Table 1
**Table Name:** `FestivalCateringOrder_UNF` (7 attributes, contains repeating group)

| OrderCode | EventDate | OrganizerEmail | VenueZone | ZoneSupervisorPhone | PackageTier | MenuItemDetails |
| --- | --- | --- | --- | --- | --- | --- |
| ORD-101 | 2025-03-12 | eco.club@campus.edu | North Quad | 555-8144 | Silver | Veg Wrap\|Starter\|5 ; Solar Salad\|Main\|11 ; Citrus Cake\|Dessert\|7 |
| ORD-102 | 2025-03-15 | dance.circle@campus.edu | East Lawn | 555-2279 | Gold | Garden Rolls\|Starter\|6 ; Fire Grill\|Main\|13 |
| ORD-103 | 2025-03-19 | robotics.hub@campus.edu | North Quad | 555-8144 | Gold | Veg Wrap\|Starter\|5 ; Kinetic Curry\|Main\|12 ; Circuit Sundae\|Dessert\|8 |
| ORD-104 | 2025-03-19 | drama.club@campus.edu | South Lawn | 555-9902 | Silver | Lantern Soup\|Starter\|4 ; Stage Stew\|Main\|10 ; Finale Tart\|Dessert\|6 |
| ORD-105 | 2025-03-22 | chess.guild@campus.edu | East Lawn | 555-2279 | Bronze | Garden Rolls\|Starter\|6 |

**Issues in Table 1**
- Repeating group (`MenuItemDetails`) violates 1NF.  
- Partial dependency: `EventDate`, `OrganizerEmail`, `VenueZone`, `PackageTier` rely on `OrderCode` only.  
- Transitive dependency: `VenueZone -> ZoneSupervisorPhone`.

---

## 3. Functional Dependencies in UNF
- **FD1:** `OrderCode -> EventDate, OrganizerEmail, VenueZone, PackageTier`  
- **FD2:** `VenueZone -> ZoneSupervisorPhone`  
- **FD3:** `DishName -> DishCategory, UnitPrice`

---

## 4. Normalization Steps

### Step 1: Convert to First Normal Form (1NF)
- **Action:** Flatten repeating menu data into individual rows.  
- **New Table (Table 2):** `FestivalOrderLine_1NF`

| OrderCode | DishName | EventDate | OrganizerEmail | VenueZone | ZoneSupervisorPhone | PackageTier | DishCategory | UnitPrice |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ORD-101 | Veg Wrap | 2025-03-12 | eco.club@campus.edu | North Quad | 555-8144 | Silver | Starter | 5 |
| ORD-101 | Solar Salad | 2025-03-12 | eco.club@campus.edu | North Quad | 555-8144 | Silver | Main | 11 |
| ORD-102 | Garden Rolls | 2025-03-15 | dance.circle@campus.edu | East Lawn | 555-2279 | Gold | Starter | 6 |
| ORD-103 | Circuit Sundae | 2025-03-19 | robotics.hub@campus.edu | North Quad | 555-8144 | Gold | Dessert | 8 |

**Primary Key:** (`OrderCode`, `DishName`)  
**Remaining Dependencies:**  
- `OrderCode -> EventDate, OrganizerEmail, VenueZone, PackageTier` (partial)  
- `VenueZone -> ZoneSupervisorPhone` (transitive)  
- `DishName -> DishCategory, UnitPrice` (repetition of item info across orders)

### Step 2: Remove Partial Dependencies (Reach 2NF)
Split order header facts from dish details.

- **Table 3:** `FestivalOrderHeader(OrderCode PK, EventDate, OrganizerEmail, VenueZone, ZoneSupervisorPhone, PackageTier)`  
- **Table 4:** `FestivalOrderDish(OrderCode PK/FK, DishName PK, DishCategory, UnitPrice)`

**Status:**  
- Header table no longer repeats per dish.  
- Dish table still repeats `DishCategory` and `UnitPrice` for each order.  
- `VenueZone -> ZoneSupervisorPhone` still transitive in Table 3.

### Step 3: Remove Transitive Dependencies (Reach 3NF)
Introduce lookup tables for zones and menu catalog, and create a pure association table.

- **Table 5:** `VenueZoneDirectory(<u>VenueZone</u>, ZoneSupervisorPhone)`  
- **Table 6:** `MenuCatalog(<u>DishName</u>, DishCategory, UnitPrice)`  
- **Table 7:** `FestivalOrderHeader(<u>OrderCode</u>, EventDate, OrganizerEmail, VenueZone*, PackageTier)`  
- **Table 8:** `FestivalOrderItem(<u>OrderCode</u>*, <u>DishName</u>*)`

**Resulting Dependencies:**  
- `OrderCode -> EventDate, OrganizerEmail, VenueZone, PackageTier` (Table 7).  
- `VenueZone -> ZoneSupervisorPhone` (Table 5).  
- `DishName -> DishCategory, UnitPrice` (Table 6).  
- `OrderCode, DishName` identifies each ordered dish in Table 8 with no leftover partial/transitive dependencies.

---

## 5. Final Schema Summary (PK underlined, FK with *)
1. `VenueZoneDirectory(<u>VenueZone</u>, ZoneSupervisorPhone)`  
2. `MenuCatalog(<u>DishName</u>, DishCategory, UnitPrice)`  
3. `FestivalOrderHeader(<u>OrderCode</u>, EventDate, OrganizerEmail, VenueZone*, PackageTier)`  
4. `FestivalOrderItem(<u>OrderCode</u>*, <u>DishName</u>*)`

All primary and foreign keys are now clearly defined, satisfying the assignment requirements.

---

## 6. Impact of Normalization
Normalization separated repeating menu entries, isolated zone contact details, and centralized dish pricing. Updates now touch a single row (e.g., change a supervisor’s phone once in `VenueZoneDirectory`), insertions no longer require null placeholders, and deletions cannot accidentally remove shared information. The 3NF schema therefore eliminates redundancy-driven anomalies and enforces integrity through explicit relationships.

---

*Prepared by: [Your Name] — Lakeview Community Arts Fair Catering Scenario*

