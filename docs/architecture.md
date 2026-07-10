# Akkar Connect — Software Architecture v1.0

## 1. Product Overview

Akkar Connect is a ride-booking and airport transportation platform built
specifically for René Mouawad Airport in North Lebanon.

The system connects four user groups:

1. Travellers
2. Transport company operators
3. Drivers
4. Airport administrators

The application has two operating timelines:

### Real-time operations

Traveller bookings are received and offered to transport companies.
Companies accept bookings and assign available drivers and vehicles.
Driver status and GPS location are sent to the traveller in real time.

### Long-term airport planning

Airport flight schedules are analyzed to predict transportation demand.
A Python Monte Carlo simulation estimates how many vehicles should be
available before flights arrive.

---

## 2. Technology Stack

### Frontend

- React 18
- Traveller mobile interface
- Company operator web portal
- Driver mobile interface
- Airport admin dashboard
- English and Arabic support
- RTL layout support

### Backend

- Laravel
- REST API
- Laravel Sanctum authentication
- Laravel Events
- Laravel Queues
- Laravel Reverb WebSockets

### Database

- PostgreSQL
- PostGIS
- Spatial points for destinations and driver locations
- Spatial routes for active and completed trips

### Supporting Services

- Redis for queues, caching and real-time state
- Python for Monte Carlo demand forecasting
- Git and GitHub for source control

---

## 3. Fixed Airport

Akkar Connect currently supports only one airport:

- Name: René Mouawad Airport
- Arabic name: مطار رينيه معوض
- IATA code: KYE

The airport is stored as application configuration rather than a database table.

Configuration file:

`config/akkar_connect.php`

---

## 4. System Modules

The backend is divided into the following modules:

### Identity Module

Responsible for:

- User accounts
- Authentication
- Roles
- Access control

### Traveller Module

Responsible for:

- Flight information
- Ride option calculation
- Booking creation
- Booking cancellation
- Driver tracking
- Ride status updates

### Fleet Module

Responsible for:

- Transport companies
- Company operators
- Vehicles
- Drivers
- Driver availability

### Dispatch Module

Responsible for:

- Incoming booking offers
- Company acceptance and rejection
- Driver assignment
- Driver acceptance
- Expiring dispatch offers

### Pooling Module

Responsible for:

- Shared van booking aggregation
- Five-minute matching windows
- Vehicle capacity checks
- Geographic destination grouping
- Pooled-trip creation

### Airport Operations Module

Responsible for:

- Flight schedule management
- Demand forecasts
- Vehicle supply commitments
- Supply-gap alerts
- Daily operational planning

### Forecasting Module

Responsible for:

- Python simulation execution
- Passenger adoption modeling
- Baggage and customs delays
- Recommended fleet size
- Simulation result storage

### Real-Time Module

Responsible for:

- New company booking alerts
- Driver job notifications
- Traveller booking updates
- Driver GPS updates
- Airport forecast and alert updates
## 5. User Roles

A single users table stores all authenticated accounts.

### Traveller

Can:

- Request ride options
- Create a booking
- View their own booking
- Cancel eligible bookings
- Track their assigned driver

### Company Operator

Can:

- View offers sent to their company
- Accept or decline booking offers
- View company drivers and vehicles
- Assign drivers to accepted bookings
- Monitor active company jobs

### Driver

Can:

- Go online or offline
- Receive assigned job offers
- Accept or decline jobs
- Send GPS locations
- Mark arrival at pickup
- Start trips
- Complete trips

### Airport Admin

Can:

- Manage flight schedules
- View demand forecasts
- View and resolve operational alerts
- Commit vehicles to forecast windows
- Trigger forecast recalculation

### System Admin

Can:

- Manage users
- Manage transport companies
- Manage system configuration
- Review system activity and simulation runs
## 6. Booking Lifecycle

A booking represents the complete traveller ride lifecycle.

Possible booking statuses:

1. PENDING
2. SEARCHING_COMPANY
3. COMPANY_OFFERED
4. COMPANY_ACCEPTED
5. DRIVER_ASSIGNED
6. DRIVER_OFFERED
7. DRIVER_ACCEPTED
8. DRIVER_ARRIVING
9. DRIVER_ARRIVED
10. IN_PROGRESS
11. COMPLETED
12. CANCELLED
13. DECLINED
14. EXPIRED

### Normal booking flow

PENDING  
→ SEARCHING_COMPANY  
→ COMPANY_OFFERED  
→ COMPANY_ACCEPTED  
→ DRIVER_ASSIGNED  
→ DRIVER_OFFERED  
→ DRIVER_ACCEPTED  
→ DRIVER_ARRIVING  
→ DRIVER_ARRIVED  
→ IN_PROGRESS  
→ COMPLETED
## 7. Database Tables

### Identity

- users
- personal_access_tokens

### Fleet

- transport_companies
- company_users
- vehicles
- drivers
- driver_locations

### Traveller and Booking

- ride_types
- ride_quotes
- bookings
- booking_status_histories

### Dispatch

- company_booking_offers
- driver_job_offers

### Pooling

- destination_zones
- pooled_trips
- pooled_trip_bookings

### Airport Operations

- flights
- forecast_windows
- vehicle_supply_commitments
- operational_alerts
- simulation_runs

## 8. Main Database Relationships

### Users

A user may be:

- a traveller
- a company operator
- a driver
- an airport administrator
- a system administrator

### Transport Company

A transport company has many:

- company users
- drivers
- vehicles
- company booking offers
- pooled trips
- vehicle supply commitments

### Vehicle

A vehicle belongs to one transport company.

A vehicle may have one currently assigned driver.

A vehicle may serve many bookings over time.

### Driver

A driver belongs to:

- one user account
- one transport company
- optionally one vehicle

A driver has many:

- driver job offers
- driver location records
- completed bookings

### Booking

A booking belongs to:

- one traveller
- one ride type
- optionally one company
- optionally one driver
- optionally one vehicle

A booking has many:

- booking status history entries
- company booking offers
- driver job offers

A shared booking may belong to one pooled trip.

### Pooled Trip

A pooled trip belongs to:

- one transport company
- one driver
- one vehicle

A pooled trip contains multiple bookings.

### Forecast Window

A forecast window has many:

- vehicle supply commitments
- operational alerts

A simulation run may generate or update multiple forecast windows.