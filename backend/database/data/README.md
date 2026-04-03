# Data Files

## reviews_4_5_estrellas.json
540 customer reviews (4-5 stars) from Google/TripAdvisor.
Import: `php artisan import:reviews`

## tours-images.tar.gz (not in git - 369MB)
Tour and page images. Ask team lead for this file.
Place it in this directory, then extract:

```bash
cd backend/storage/app/public
tar -xzf ../../../database/data/tours-images.tar.gz
```

This creates `tours/` and `pages/` directories with all images.
Then run `php artisan storage:link` if not done already.
