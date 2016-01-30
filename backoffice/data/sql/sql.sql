    SELECT
          apartment.id            AS prod_id,
          apartment.name          AS prod_name,
          apartment.url           AS url,
          apartment.score         AS score,
          apartment.max_capacity  AS capacity,
          apartment.city_id,
          apartment.country_id,
          media.img1              AS img1,
          apartment.bedroom_count AS bedroom_count,
          apartment.square_meters AS square_meters,
          apartment.address       AS address,
          (rate_av.availability)  AS availability,
          (rate_av.price)         AS price_av,
          rates.id                AS rate_id,
          apartment.currency_id,
          rates.name              AS rate_name
        FROM ga_rel_apartel_type_apartment AS rel_apartel
          INNER JOIN ga_apartel_type AS apartel_type ON rel_apartel.apartel_type_id = apartel_type.id
          INNER JOIN ga_apartels AS apartel ON apartel_type.apartel_id = apartel.id
          INNER JOIN ga_apartments AS apartment ON rel_apartel.apartment_id = apartment.id
          INNER JOIN ga_cities AS city ON apartment.city_id = city.id
          INNER JOIN ga_apartment_images AS media ON apartment.id = media.apartment_id
          INNER JOIN ga_apartment_rates AS rates ON apartment.id = rates.apartment_id
                                                    AND rates.active = 1
                                                    AND rates.min_stay <= 2
                                                    AND rates.max_stay >= 2
                                                    AND rates.release_period_start <= 1
                                                    AND rates.release_period_end >= 1
          INNER JOIN ga_apartment_inventory AS rate_av ON rates.id = rate_av.rate_id and rate_av.availability = 1
        WHERE apartel.slug = 'hollywood-rubix' AND apartment.status = 5
              AND rates.capacity >= 2
              AND rate_av.date >= '2015-10-15'
              AND rate_av.date < '2015-10-17'
              AND apartment.bedroom_count IN (1)
        ORDER BY apartment.name ASC, rate_av.availability ASC;
