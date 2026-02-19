# AdWords Campaign Budget Management System

A full-stack application for managing AdWords campaign budgets with intelligent cost generation following strict business rules.

## Features

✅ **Campaign Management**
- Create, update, pause, and resume campaigns
- Real-time budget adjustments
- Automatic budget history tracking

✅ **Cost Generation Algorithm**
- Generates 1-10 random costs per day
- Rule 1: Daily cumulated cost ≤ 2x current budget at generation time
- Rule 2: Monthly cumulated cost ≤ sum of max daily budgets for each day

✅ **Data Visualization**
- Daily cost summary table
- Interactive charts (budget vs costs trends)
- Real-time statistics dashboard
- 3-month historical data view

## Tech Stack

### Backend
- **Framework:** Laravel 12
- **Database:** PostgreSQL / SQLite
- **Language:** PHP 8.2+

### Frontend
- **Framework:** React 19 + TypeScript
- **Build Tool:** Vite
- **Styling:** Tailwind CSS
- **Charts:** Recharts
- **Routing:** React Router
- **HTTP Client:** Axios

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- PostgreSQL (optional, SQLite works too)

### Backend Setup

1. Navigate to backend directory:
```bash
cd backend
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations and seed database:
```bash
php artisan migrate:fresh --seed
```

6. Generate costs for campaigns (3 months):
```bash
php artisan campaign:generate-costs --all --from=2025-11-19 --to=2026-02-19
```

7. Start the server:
```bash
php artisan serve
```

Backend API will be available at `http://localhost:8000/api`

### Frontend Setup

1. Navigate to frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Start development server:
```bash
npm run dev
```

Frontend will be available at `http://localhost:5173`

## API Endpoints

### Campaigns
- `GET /api/campaigns` - List all campaigns
- `POST /api/campaigns` - Create campaign
- `GET /api/campaigns/{id}` - Get campaign details
- `PUT /api/campaigns/{id}` - Update campaign
- `DELETE /api/campaigns/{id}` - Delete campaign
- `POST /api/campaigns/{id}/pause` - Pause campaign
- `POST /api/campaigns/{id}/resume` - Resume campaign

### Budget Management
- `POST /api/campaigns/{id}/budget` - Update budget
- `GET /api/campaigns/{id}/budget-history` - Get budget history

### Cost Data
- `GET /api/campaigns/{id}/costs` - Get costs
- `GET /api/campaigns/{id}/daily-summary` - Get daily summary
- `POST /api/campaigns/{id}/generate-costs` - Generate costs (dev/testing)
- `GET /api/campaigns/{id}/stats` - Get statistics

## Database Schema

### campaigns
- `id`, `user_id`, `name`, `current_daily_budget`, `is_active`, `timestamps`

### budget_histories
- `id`, `campaign_id`, `previous_budget`, `new_budget`, `changed_at`

### costs
- `id`, `campaign_id`, `amount`, `generated_at`, `budget_at_generation`, `daily_limit_at_generation`, `created_at`

## Sample Data

The seeder creates 3 test campaigns:

1. **Stable Budget Campaign** - Constant $150 daily budget
2. **Dynamic Budget Campaign** - 6 budget changes over 3 months
3. **Pause/Resume Campaign** - Multiple pause/resume cycles

Run the cost generator to populate with realistic data following all business rules.

## Artisan Commands

```bash
# Generate costs for all campaigns
php artisan campaign:generate-costs --all --from=2025-11-19 --to=2026-02-19

# Generate costs for specific campaign
php artisan campaign:generate-costs 1 --from=2025-11-19 --to=2026-02-19

# View campaigns in database
php artisan tinker
>>> App\Models\Campaign::with('budgetHistories')->get()
```

## Business Rules Implementation

### Daily Cost Rule
Each cost generation checks:
- Current budget at that specific timestamp
- Daily limit = 2x current budget
- Cumulative costs for the day so far
- Only generates if remaining capacity exists

### Monthly Cost Rule
Each cost generation verifies:
- Month start date
- For each day in month: find max budget active that day
- Sum all max daily budgets = monthly limit
- Cumulative costs for month so far
- Only generates if within monthly limit

## Project Structure

```
ad_words_project/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/Api/  # API controllers
│   │   ├── Models/                # Eloquent models
│   │   ├── Services/              # Business logic
│   │   └── Console/Commands/      # Artisan commands
│   ├── database/
│   │   ├── migrations/            # Database migrations
│   │   └── seeders/               # Data seeders
│   └── routes/api.php             # API routes
├── frontend/
│   └── src/
│       ├── components/            # React components
│       ├── hooks/                 # Custom hooks
│       ├── pages/                 # Page components
│       ├── services/              # API services
│       ├── types/                 # TypeScript types
│       └── utils/                 # Utility functions
└── README.md
```

## License

MIT
