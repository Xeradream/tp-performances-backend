Vous pouvez utiliser ce [GSheets](https://docs.google.com/spreadsheets/d/13Hw27U3CsoWGKJ-qDAunW9Kcmqe9ng8FROmZaLROU5c/copy?usp=sharing) pour suivre l'évolution de l'amélioration de vos performances au cours du TP 

## Question 2 : Utilisation Server Timing API

**Temps de chargement initial de la page** : 31.33s

**Choix des méthodes à analyser** :

- `getCheapestRoom` 16.62s
- `getMeta` 4.12s
- `getReviews` 9.28s



## Question 3 : Réduction du nombre de connexions PDO

**Temps de chargement de la page** : 29.6s

**Temps consommé par `getDB()`** 

- **Avant** 1.18s

- **Après** 2.62ms


## Question 4 : Délégation des opérations de filtrage à la base de données

**Temps de chargement globaux** 

- **Avant** 29.6s

- **Après** 23s


#### Amélioration de la méthode getMeta et donc de la méthode getMetas:

- **Avant** 4.12s

```sql
-- SELECT * FROM wp_usermeta
```

- **Après** 166.73ms

```sql
-- SELECT meta_key,meta_value FROM wp_usermeta WHERE user_id = :userid
```



#### Amélioration de la méthode getReviews :

- **Avant** 9.28s

```sql
-- SELECT * FROM wp_posts, wp_postmeta 
WHERE wp_posts.post_author = :hotelId 
AND wp_posts.ID = wp_postmeta.post_id AND meta_key = 'rating' 
AND post_type = 'review'
```

- **Après** 6.18s

```sql
-- SELECT ROUND(AVG(meta_value)) AS rating, 
COUNT(meta_value) AS count FROM wp_posts, wp_postmeta 
WHERE wp_posts.post_author = :hotelId AND wp_posts.ID = wp_postmeta.post_id 
AND meta_key = 'rating' AND post_type = 'review'
```



#### Amélioration de la méthode getCheapestRoom :

- **Avant** 16.62s

```sql
-- SELECT * FROM wp_posts WHERE post_author = :hotelId AND post_type = 'room'
```

- **Après** 9.24s

```sql
-- SELECT post.ID,
        post.post_title AS title,
        PriceData.meta_value AS price, 
        SurfaceData.meta_value AS surface, 
        TypeData.meta_value AS type, 
        BedroomsCountData.meta_value AS bedrooms, 
        BathroomsCountData.meta_value AS bathrooms
        FROM wp_posts AS post
        INNER JOIN wp_postmeta AS PriceData ON post.ID = PriceData.post_id AND PriceData.meta_key = 'price'
        INNER JOIN wp_postmeta AS SurfaceData ON post.ID = SurfaceData.post_id AND SurfaceData.meta_key = 'surface'
        INNER JOIN wp_postmeta AS TypeData ON post.ID = TypeData.post_id AND TypeData.meta_key = 'type'
        INNER JOIN wp_postmeta AS BedroomsCountData ON post.ID = BedroomsCountData.post_id AND BedroomsCountData.meta_key = 'bedrooms_count'
        INNER JOIN wp_postmeta AS BathroomsCountData ON post.ID = BathroomsCountData.post_id AND BathroomsCountData.meta_key = 'bathrooms_count'"
```



## Question 5 : Réduction du nombre de requêtes SQL pour getMeta

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | 1201      | 601       |
 | Temps de getMeta	       | 4.12s	   | 166,37ms

## Question 6 : Création d'un service basé sur une seule requête SQL

|                              | **Avant** | **Après** |
|------------------------------|-----------|-----------|
| Nombre d'appels de `getDB()` | 601       | 1         |
| Temps de chargement global   | 23s       | 4,30s     |

**Requête SQL**

SELECT
            user.ID AS id,
            user.display_name AS name,
            address_1Data.meta_value       as hotel_address_1,
            address_2Data.meta_value       as hotel_address_2,
            address_cityData.meta_value    as hotel_address_city,
            address_zipData.meta_value     as hotel_address_zip,
            address_countryData.meta_value as hotel_address_country,
            geo_latData.meta_value         as geo_lat,
            geo_lngData.meta_value         as geo_lng,
            phoneData.meta_value           as phone,
            coverImageData.meta_value      as coverImage,
            postData.ID                    as cheapestRoomid,
            postData.price                 as price,
            postData.surface               as surface,
            postData.bedroom               as bedRoomsCount,
            postData.bathroom              as bathRoomsCount,
            postData.type                  as type,
            COUNT(reviewData.meta_value)   as ratingCount,
            AVG(reviewData.meta_value)     as rating 
            FROM
            wp_users AS USER
            /* Jointure avec les métas de l'hôtel */
            INNER JOIN wp_usermeta as address_1Data       ON address_1Data.user_id       = USER.ID     AND address_1Data.meta_key       = 'address_1'
            INNER JOIN wp_usermeta as address_2Data       ON address_2Data.user_id       = USER.ID     AND address_2Data.meta_key       = 'address_2'
            INNER JOIN wp_usermeta as address_cityData    ON address_cityData.user_id    = USER.ID     AND address_cityData.meta_key    = 'address_city'
            INNER JOIN wp_usermeta as address_zipData     ON address_zipData.user_id     = USER.ID     AND address_zipData.meta_key     = 'address_zip'
            INNER JOIN wp_usermeta as address_countryData ON address_countryData.user_id = USER.ID     AND address_countryData.meta_key = 'address_country'
            INNER JOIN wp_usermeta as geo_latData         ON geo_latData.user_id         = USER.ID     AND geo_latData.meta_key         = 'geo_lat'
            INNER JOIN wp_usermeta as geo_lngData         ON geo_lngData.user_id         = USER.ID     AND geo_lngData.meta_key         = 'geo_lng'
            INNER JOIN wp_usermeta as coverImageData      ON coverImageData.user_id      = USER.ID     AND coverImageData.meta_key      = 'coverImage'
            INNER JOIN wp_usermeta as phoneData           ON phoneData.user_id           = USER.ID     AND phoneData.meta_key           = 'phone'
            INNER JOIN wp_posts    as rating_postData     ON rating_postData.post_author = USER.ID     AND rating_postData.post_type    = 'review'
            INNER JOIN wp_postmeta as reviewData          ON reviewData.post_id = rating_postData.ID   AND reviewData.meta_key          = 'rating'
            /* Jointure avec les métas de la chambre la moins chère */
            INNER JOIN (SELECT
                post.ID,
                post.post_author,
                MIN(CAST(priceData.meta_value AS UNSIGNED)) AS price,
                CAST(surfaceData.meta_value  AS UNSIGNED) AS surface,
                CAST(roomsData.meta_value AS UNSIGNED) AS bedroom,
                CAST(bathRoomsData.meta_value AS UNSIGNED) AS bathroom,
                typeData.meta_value AS type
                FROM
                tp.wp_posts AS post
                INNER JOIN tp.wp_postmeta AS priceData ON post.ID = priceData.post_id AND priceData.meta_key = 'price'
                INNER JOIN wp_postmeta as surfaceData ON surfaceData.post_id = post.ID AND surfaceData.meta_key = 'surface'
                INNER JOIN wp_postmeta as roomsData ON roomsData.post_id = post.ID AND roomsData.meta_key = 'bedrooms_count'
                INNER JOIN wp_postmeta as bathRoomsData ON bathRoomsData.post_id = post.ID AND bathRoomsData.meta_key = 'bathrooms_count'
                INNER JOIN wp_postmeta as typeData ON typeData.post_id = post.ID AND typeData.meta_key = 'type'
                WHERE
                post.post_type = 'room'
                GROUP BY
                post.ID
            ) AS postData ON user.ID = postData.post_author
            GROUP BY user.ID
            ORDER BY `cheapestRoomId` ASC

## Question 7 : ajout d'indexes SQL

**Indexes ajoutés**

- `TABLE` : `COLONNES`
- `TABLE` : `COLONNES`
- `TABLE` : `COLONNES`

**Requête SQL d'ajout des indexes** 

```sql
-- REQ SQL CREATION INDEXES
```

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `UnoptimizedService`           | TEMPS       | TEMPS        |
| `OneRequestService`            | TEMPS       | TEMPS        |
[Filtres à utiliser pour mesurer le temps de chargement](http://localhost/?types%5B%5D=Maison&types%5B%5D=Appartement&price%5Bmin%5D=200&price%5Bmax%5D=230&surface%5Bmin%5D=130&surface%5Bmax%5D=150&rooms=5&bathRooms=5&lat=46.988708&lng=3.160778&search=Nevers&distance=30)




## Question 8 : restructuration des tables

**Temps de chargement de la page**

| Temps de chargement de la page | Sans filtre | Avec filtres |
|--------------------------------|-------------|--------------|
| `OneRequestService`            | TEMPS       | TEMPS        |
| `ReworkedHotelService`         | TEMPS       | TEMPS        |

[Filtres à utiliser pour mesurer le temps de chargement](http://localhost/?types%5B%5D=Maison&types%5B%5D=Appartement&price%5Bmin%5D=200&price%5Bmax%5D=230&surface%5Bmin%5D=130&surface%5Bmax%5D=150&rooms=5&bathRooms=5&lat=46.988708&lng=3.160778&search=Nevers&distance=30)

### Table `hotels` (200 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```

### Table `rooms` (1 200 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```

### Table `reviews` (19 700 lignes)

```SQL
-- REQ SQL CREATION TABLE
```

```SQL
-- REQ SQL INSERTION DONNÉES DANS LA TABLE
```


## Question 13 : Implémentation d'un cache Redis

**Temps de chargement de la page**

| Sans Cache | Avec Cache |
|------------|------------|
| TEMPS      | TEMPS      |
[URL pour ignorer le cache sur localhost](http://localhost?skip_cache)

## Question 14 : Compression GZIP

**Comparaison des poids de fichier avec et sans compression GZIP**

|                       | Sans  | Avec  |
|-----------------------|-------|-------|
| Total des fichiers JS | POIDS | POIDS |
| `lodash.js`           | POIDS | POIDS |

## Question 15 : Cache HTTP fichiers statiques

**Poids transféré de la page**

- **Avant** : POIDS
- **Après** : POIDS

## Question 17 : Cache NGINX

**Temps de chargement cache FastCGI**

- **Avant** : TEMPS
- **Après** : TEMPS

#### Que se passe-t-il si on actualise la page après avoir coupé la base de données ?

REPONSE

#### Pourquoi ?

REPONSE
