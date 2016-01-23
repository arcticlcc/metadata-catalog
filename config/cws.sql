-- Materialized View: records

CREATE MATERIALIZED VIEW records AS
 WITH rdate AS (
         SELECT product.productid,
            jsonb_array_elements(product.json #> '{metadata,resourceInfo,citation,date}'::text[]) AS json
           FROM product
        ), roles AS (
         SELECT p.productid,
            q."organizationName" AS org,
            q."contactId" AS contactid,
            q.role AS crole
           FROM product p,
            LATERAL ( SELECT x."contactId",
                    x."organizationName",
                    r.role
                   FROM jsonb_to_recordset(( SELECT product.json #> '{contact}'::text[]
                           FROM product
                          WHERE product.productid = p.productid)) x("contactId" text, "organizationName" text)
                     JOIN ( SELECT x_1."contactId",
                            x_1.role
                           FROM jsonb_to_recordset(( SELECT product.json #> '{metadata,resourceInfo,citation,responsibleParty}'::text[]
                                   FROM product
                                  WHERE product.productid = p.productid)) x_1("contactId" text, role text)) r USING ("contactId")
                  WHERE NOT x."organizationName" = 'null'::text) q
        )
 SELECT prod.productid::text AS identifier,
    'gmd:MD_Metadata'::text AS typename,
    'http://www.isotc211.org/2005/gmd'::text AS schema,
    COALESCE(prod.json #>> '{metadata,metadataInfo,metadataLastUpdate}'::text[], now()::text) AS insert_date,
    regexp_replace(XMLPARSE(DOCUMENT prod.xml STRIP WHITESPACE)::text, 'encoding="UTF-8"'::text, ''::text) AS xml,
    regexp_replace(regexp_replace(prod.xml, '<[^<]+>'::text, ''::text, 'g'::text), '(\r|\n|\s|\t)+'::text, ' '::text, 'g'::text) AS anytext,
    b.bbox AS wkt_geometry,
    --b.the_geom,
    prod.json #>> '{metadata,resourceInfo,citation,title}'::text[] AS title,
    k.keywords,
    ln.links,
    'local'::text AS mdsource,
    prod.json #>> '{metadata,resourceInfo,abstract}'::text[] AS abstract,
    'theme'::text AS keywordstype,
    f.format,
    prod.json #>> '{metadata,resourceInfo,citation,onlineResource,0,uri}'::text[] AS source,
    prod.json #>> '{metadata,metadataInfo,metadataCreationDate}'::text[] AS date,
    prod.json #>> '{metadata,resourceInfo,resourceType}'::text[] AS type,
    'urn:ogc:def:crs:EPSG::4326'::text AS crs,
    prod.json #>> '{metadata,resourceInfo,citation,alternateTitle}'::text[] AS title_alternate,
    ( SELECT rdate.json ->> 'date'::text
           FROM rdate
          WHERE rdate.json @> '{"dateType": "lastUpdate"}'::jsonb AND rdate.productid = prod.productid) AS date_modified,
    ( SELECT rdate.json ->> 'date'::text
           FROM rdate
          WHERE rdate.json @> '{"dateType": "revision"}'::jsonb AND rdate.productid = prod.productid) AS date_revision,
    ( SELECT rdate.json ->> 'date'::text
           FROM rdate
          WHERE rdate.json @> '{"dateType": "creation"}'::jsonb AND rdate.productid = prod.productid) AS date_creation,
    ( SELECT rdate.json ->> 'date'::text
           FROM rdate
          WHERE rdate.json @> '{"dateType": "publication"}'::jsonb AND rdate.productid = prod.productid) AS date_publication,
    ( SELECT roles.org
           FROM roles
          WHERE roles.contactid = (prod.json #>> '{metadata,resourceInfo,pointOfContact,0,contactId}'::text[]) AND roles.productid = prod.productid
         LIMIT 1) AS organization,
    NULL::text AS securityconstraints,
    prod.json #>> '{metadata,metadataInfo,parentMetadata,identifier,0,identifier}'::text[] AS parentidentifier,
    tc.topic AS topicategory,
    l.lang AS resourcelanguage,
    NULL::text AS geodescode,
    NULL::text AS denominator,
    NULL::text AS distancevalue,
    NULL::text AS distanceuom,
    prod.json #>> '{metadata,resourceInfo,resourceTimePeriod,beginPosition}'::text[] AS time_begin,
    prod.json #>> '{metadata,resourceInfo,resourceTimePeriod,endPosition}'::text[] AS time_end,
    NULL::text AS servicetype,
    NULL::text AS servicetypeversion,
    NULL::text AS operation,
    NULL::text AS couplingtype,
    NULL::text AS operateson,
    NULL::text AS operatesonidentifier,
    NULL::text AS operatesoname,
    NULL::text AS degree,
    NULL::text AS accessconstraints,
    NULL::text AS otherconstraints,
    NULL::text AS classification,
    lm.limits AS conditionapplyingtoaccessanduse,
    NULL::text AS lineage,
    NULL::text AS responsiblepartyrole,
    NULL::text AS specificationtitle,
    NULL::text AS specificationdate,
    NULL::text AS specificationdatetype,
    ( SELECT string_agg(roles.org, ','::text) AS string_agg
           FROM roles
          WHERE roles.crole = 'originator'::text AND roles.productid = prod.productid) AS creator,
    ( SELECT string_agg(roles.org, ','::text) AS string_agg
           FROM roles
          WHERE roles.crole = 'publisher'::text AND roles.productid = prod.productid) AS publisher,
    ( SELECT string_agg(roles.org, ','::text) AS string_agg
           FROM roles
          WHERE (roles.crole = ANY (ARRAY['contributor'::text, 'principalInvestigator'::text, 'author'::text, 'coauthor'::text, 'collaborator'::text, 'editor'::text, 'coPrincipalInvestigator'::text])) AND roles.productid = prod.productid) AS contributor,
    NULL::text AS relation
   FROM product prod,
    LATERAL ( SELECT st_astext(st_convexhull(st_collect(g.geom))) AS bbox--,
            --st_convexhull(st_collect(g.geom)) AS the_geom
           FROM ( SELECT
                        CASE
                            WHEN ge.geo ? 'geometry'::text THEN st_geomfromgeojson(ge.geo ->> 'geometry'::text)
                            WHEN ge.geo ? 'features'::text THEN st_geomfromgeojson(jsonb_array_elements(ge.geo #> '{features}'::text[]) ->> 'geometry'::text)
                            WHEN ge.geo ? 'coordinates'::text THEN st_geomfromgeojson(ge.geo::text)
                            WHEN ge.geo ? 'geometries'::text THEN st_geomfromgeojson(jsonb_array_elements(ge.geo #> '{geometries}'::text[])::text)
                            ELSE NULL::geometry
                        END AS geom
                   FROM ( SELECT jsonb_array_elements(jsonb_array_elements(product_1.json #> '{metadata,resourceInfo,extent}'::text[]) #> '{geographicElement}'::text[]) AS geo
                           FROM product product_1
                          WHERE product_1.productid = prod.productid) ge) g) b,
    LATERAL ( SELECT string_agg(p.keyword, ','::text) AS keywords
           FROM ( SELECT jsonb_array_elements_text(jsonb_array_elements(product.json #> '{metadata,resourceInfo,keyword}'::text[]) #> '{keyword}'::text[]) AS keyword
                   FROM product
                  WHERE product.productid = prod.productid) p) k,
    LATERAL ( SELECT string_agg(p.item, ','::text) AS lang
           FROM ( SELECT jsonb_array_elements(product.json #> '{metadata,resourceInfo,locale}'::text[]) #>> '{language}'::text[] AS item
                   FROM product
                  WHERE product.productid = prod.productid) p) l,
    LATERAL ( SELECT string_agg(p.item, ','::text) AS format
           FROM ( SELECT jsonb_array_elements(product.json #> '{metadata,resourceInfo,resourceNativeFormat}'::text[]) #>> '{formatName}'::text[] AS item
                   FROM product
                  WHERE product.productid = prod.productid) p) f,
    LATERAL ( SELECT string_agg(p.item, ','::text) AS topic
           FROM ( SELECT jsonb_array_elements_text(product.json #> '{{metadata,resourceInfo,topicCategory}}'::text[]) AS item
                   FROM product
                  WHERE product.productid = prod.productid) p) tc,
    LATERAL ( SELECT string_agg(p.item, '|'::text) AS limits
           FROM ( SELECT jsonb_array_elements_text(product.json #> '{{metadata,resourceInfo,constraint,useLimitation}}'::text[]) AS item
                   FROM product
                  WHERE product.productid = prod.productid) p) lm,
    LATERAL ( SELECT string_agg(((((((p.obj ->> 'name'::text) || ','::text) || (p.obj ->> 'description'::text)) || ','::text) || (p.obj ->> 'function'::text)) || ','::text) || (p.obj ->> 'uri'::text), '^'::text) AS links
           FROM ( SELECT jsonb_array_elements(jsonb_array_elements(jsonb_array_elements(product.json #> '{metadata,distributionInfo}'::text[]) #> '{distributorTransferOptions}'::text[]) #> '{online}'::text[]) AS obj
                   FROM product
                  WHERE product.productid = prod.productid) p) ln
WITH DATA;

-- Index: records_identifier_idx

-- DROP INDEX records_identifier_idx;

CREATE UNIQUE INDEX records_identifier_idx
  ON records
  USING btree
  (identifier COLLATE pg_catalog."default");
