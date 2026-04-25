-- ============================================================
-- FIX: Reactivar lotes con stock que quedaron mal inactivos
-- Causa: bug en descontarStockFIFO (MySQL evalúa SET en orden)
-- Afecta: 38 productos, 51 lotes
-- Fecha fix: 2026-04-23
-- ============================================================

-- Producto: PIL BIOGURT YOGURT DURAZNO 1L (ID 10)
UPDATE lotes SET activo = 1 WHERE id = 1251;
UPDATE lotes SET activo = 1 WHERE id = 1635;

-- Producto: PIL YOGURT FRUTILLA 1L (ID 11)
UPDATE lotes SET activo = 1 WHERE id = 1638;

-- Producto: GINGER DE SALVIETTI 2L (ID 49)
UPDATE lotes SET activo = 1 WHERE id = 1358;
UPDATE lotes SET activo = 1 WHERE id = 1470;

-- Producto: SALVIETE KINOTTO 2.5L (ID 75)
UPDATE lotes SET activo = 1 WHERE id = 870;
UPDATE lotes SET activo = 1 WHERE id = 1469;
UPDATE lotes SET activo = 1 WHERE id = 1592;

-- Producto: TORTITACOS MEDIANO 400G (ID 115)
UPDATE lotes SET activo = 1 WHERE id = 1495;

-- Producto: QUESO LAMINADO 140G (ID 116)
UPDATE lotes SET activo = 1 WHERE id = 1221;
UPDATE lotes SET activo = 1 WHERE id = 1395;
UPDATE lotes SET activo = 1 WHERE id = 1496;

-- Producto: FRIKROSS CHICHARRON (ID 167)
UPDATE lotes SET activo = 1 WHERE id = 1267;
UPDATE lotes SET activo = 1 WHERE id = 1436;

-- Producto: FRIKROSS PAPAS PICANTES 80G (ID 169)
UPDATE lotes SET activo = 1 WHERE id = 1149;
UPDATE lotes SET activo = 1 WHERE id = 1435;

-- Producto: DR PICKS CONITOS 230G (ID 181)
UPDATE lotes SET activo = 1 WHERE id = 1503;

-- Producto: DELIZIA HELADOS COPA CAPUCHINO 160ML (ID 373)
UPDATE lotes SET activo = 1 WHERE id = 1431;

-- Producto: PIL GRECO NATURAL SIN SABOR 160G (ID 417)
UPDATE lotes SET activo = 1 WHERE id = 1523;
UPDATE lotes SET activo = 1 WHERE id = 1641;

-- Producto: PIL YOGURT DURAZNO 1L (ID 423)
UPDATE lotes SET activo = 1 WHERE id = 1639;

-- Producto: NESCAFE ORGINAL 50G (ID 429)
UPDATE lotes SET activo = 1 WHERE id = 1571;

-- Producto: AQUARIUS PERA 500ML (ID 439)
UPDATE lotes SET activo = 1 WHERE id = 1690;

-- Producto: QUESO MOZZARELLA FAMOSA 400G (ID 470)
UPDATE lotes SET activo = 1 WHERE id = 1396;
UPDATE lotes SET activo = 1 WHERE id = 1613;

-- Producto: DEL VALLE FRESH NARANJA 300ML (ID 548)
UPDATE lotes SET activo = 1 WHERE id = 1354;

-- Producto: KRAKS NACHOS CLASICO 200G (ID 611)
UPDATE lotes SET activo = 1 WHERE id = 996;
UPDATE lotes SET activo = 1 WHERE id = 1610;

-- Producto: KRAKS NACHO CHILE LIMON 200G (ID 612)
UPDATE lotes SET activo = 1 WHERE id = 1609;

-- Producto: PINZONES PIZZA 220G (ID 613)
UPDATE lotes SET activo = 1 WHERE id = 1369;

-- Producto: DELIZIA HELADO CONO GRIEGO 140G (ID 630) — 5 unidades
UPDATE lotes SET activo = 1 WHERE id = 1703;

-- Producto: GRECO FRUTILLA VASITO 160G (ID 686)
UPDATE lotes SET activo = 1 WHERE id = 1364;
UPDATE lotes SET activo = 1 WHERE id = 1458;
UPDATE lotes SET activo = 1 WHERE id = 1642;

-- Producto: PIL JUGUITO DURAZNO 150ml (ID 698)
UPDATE lotes SET activo = 1 WHERE id = 1385;

-- Producto: FOUR LOKO GOL 695ML (ID 706)
UPDATE lotes SET activo = 1 WHERE id = 1397;

-- Producto: MERMELADA ORIETA FRUTILLA 500G (ID 708)
UPDATE lotes SET activo = 1 WHERE id = 1403;

-- Producto: CHICOLAC CREMA 120ML (ID 717) — 4 unidades
UPDATE lotes SET activo = 1 WHERE id = 1419;

-- Producto: SANTE SABOR COLAGENO 1L (ID 725)
UPDATE lotes SET activo = 1 WHERE id = 1453;

-- Producto: POWERADE CONTRATAQUE COOL CITRUS 990ML (ID 739)
UPDATE lotes SET activo = 1 WHERE id = 1582;

-- Producto: POWERADE ATAQUE CON VITAMINAS 990ML (ID 740)
UPDATE lotes SET activo = 1 WHERE id = 1583;

-- Producto: KRISKAO 220G (ID 743)
UPDATE lotes SET activo = 1 WHERE id = 1606;

-- Producto: DELIZIA DISFRUT DURAZNO 175ML (ID 758)
UPDATE lotes SET activo = 1 WHERE id = 1720;

-- Producto: MARGARINA REYNA DE 100G (ID 759)
UPDATE lotes SET activo = 1 WHERE id = 1723;

-- Producto: FINI GOMITAS BESOS 90G (ID 763)
UPDATE lotes SET activo = 1 WHERE id = 1737;

-- Producto: FINI GOMITAS DENTADURAS 90G (ID 765)
UPDATE lotes SET activo = 1 WHERE id = 1739;

-- Producto: FINI GOMITAS GUSANOS BRILLO 90G (ID 766)
UPDATE lotes SET activo = 1 WHERE id = 1740;

-- Producto: FINI GOMITAS OSITOS 90G (ID 767)
UPDATE lotes SET activo = 1 WHERE id = 1741;

-- Producto: FINI GOMITAS CHICLE SANDIA ACIDA 80G (ID 768)
UPDATE lotes SET activo = 1 WHERE id = 1742;

-- Producto: FINI GOMITAS CHICLE ENSALADA DE FRUTAS ACIDAS 80G (ID 769)
UPDATE lotes SET activo = 1 WHERE id = 1743;

-- Producto: QUESO MOZZARELLA LAMINADO 200G (ID 777)
UPDATE lotes SET activo = 1 WHERE id = 1784;

-- Producto: FANTA NARANJA 3L (ID 54)
UPDATE lotes SET activo = 1 WHERE id = 1176;
