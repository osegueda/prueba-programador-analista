/*CONSULTA #1*/
SELECT 
    CONCAT(c.FirstName," ",c.LastName) AS "Nombre Completo", 
    Customer.Country AS "Pais", 
    Invoice.Total AS "Monto total gastado"
FROM 
customer AS Customer INNER JOIN 
invoice AS Invoice

ON Invoice.CustomerId = Customer.CustomerId
ORDER BY Invoice.Total DESC;
/*----------------------------------------------------*/

/*CONSULTA #2*/

SELECT 
Genre.Name AS "Genero",
COUNT(Track.TrackId) AS Total 
 
FROM  
genre AS Genre INNER JOIN 
track AS Track ON Track.GenreId = Genre.GenreId

GROUP BY Genre.GenreId;
/*----------------------------------------------------*/

/*CONSULTA #3*/
SELECT 
Country.FirstName, 
Country.LastName, 
Country.Company, 
Country.Country  

FROM  customer AS Country

WHERE Country.Country <> "USA"
ORDER BY Country.Country;
/*---------------------------------------------------*/