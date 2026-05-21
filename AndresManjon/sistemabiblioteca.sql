-- 1. Tabla de Roles
CREATE TABLE IF NOT EXISTS Rol (
    id_rol INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    fecha_de_inicio INTEGER NOT NULL,
    fecha_de_caducidad INTEGER NOT NULL
);

INSERT INTO Rol (nombre, fecha_de_inicio, fecha_de_caducidad) VALUES
('Estudiante', 20260101, 20301231), 
('Profesor', 20260101, 20301231),
('Admin', 20260101, 20301231);

-- 2. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS Usuario (
    id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    username TEXT NOT NULL,
    contrasenia TEXT NOT NULL,
    codigo_de_carnet TEXT UNIQUE,
    id_rol INTEGER,
    FOREIGN KEY (id_rol) REFERENCES Rol(id_rol)
);

-- Insertar Admins (Rol 3)
INSERT INTO Usuario (nombre, username, contrasenia, codigo_de_carnet, id_rol) VALUES 
('Ana García', 'ana.garcia.adm', 'AnaG26', '26-ADM-ANGA', 3), 
('Roberto Sanz', 'roberto.sanz.adm', 'RoberS26', '26-ADM-ROSA', 3), 
('Lucía Torres', 'lucia.torres.adm', 'LuciaT26', '26-ADM-LUTO', 3), 
('Marcos Peña', 'marcos.pena.adm', 'MarcosP26', '26-ADM-MAPE', 3);

-- Insertar Profesores (Rol 2)
INSERT INTO Usuario (nombre, username, contrasenia, codigo_de_carnet, id_rol) VALUES 
('Elena Cano', 'elena.cano.prof', 'ElenaC26', '26-PRO-ELCA', 2), 
('Javier López', 'javier.lopez.prof', 'JaviL26', '26-PRO-JALO', 2), 
('Marta Ruiz', 'marta.ruiz.prof', 'MartaR26', '26-PRO-MARU', 2), 
('Diego Velasco', 'diego.velasco.prof', 'DiegoV26', '26-PRO-DIVE', 2);

-- Insertar Alumnos (Rol 1) - Año 2026
INSERT INTO Usuario (nombre, username, contrasenia, codigo_de_carnet, id_rol) VALUES 
('Hugo Martínez', 'hugo.martinez.inf', 'HugoM26', '26-INF-HUMA', 1), 
('Sara León', 'sara.leon.1pri', 'SaraL26', '26-1PRI-SALE', 1), 
('Daniel Navarro', 'dani.navarro.2pri', 'DaniN26', '26-2PRI-DANA', 1), 
('Irene Castillo', 'irene.castillo.3pri', 'IreneC26', '26-3PRI-IRCA', 1), 
('Alejandro Gómez', 'ale.gomez.4pri', 'AleG26', '26-4PRI-ALGO', 1), 
('Paula Jiménez', 'paula.jimenez.5pri', 'PauJ26', '26-5PRI-PAJI', 1), 
('Adrián Molina', 'adrian.molina.6pri', 'AdriM26', '26-6PRI-ADMO', 1), 
('Beatriz Díaz', 'beatriz.diaz.3pri', 'BeaD26', '26-3PRI-BEDI', 1), 
('Mario Blanco', 'mario.blanco.4pri', 'MarioB26', '26-4PRI-MABL', 1), 
('Natalia Crespo', 'natalia.crespo.6pri', 'NataC26', '26-6PRI-NACR', 1);

-- 3. Tabla de Catálogo (Libros)
CREATE TABLE IF NOT EXISTS Libro (
    id_libro INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    codigo_de_barra INTEGER NOT NULL,
    autor TEXT NOT NULL,
    isbn TEXT NOT NULL UNIQUE,
    ubicacion_por_colores TEXT NOT NULL,
    estado_de_actividad TEXT DEFAULT 'Disponible'
);

INSERT INTO Libro (titulo, autor, isbn, ubicacion_por_colores, codigo_de_barra, estado_de_actividad) VALUES 
('Tu primer VOX de Cuentos del Mundo', 'Marie-Pierre Levallois', '9788483326053', 'Rojo', 8332605, 'Disponible'),
('Niños y Niñas del Mundo', 'Núria Roca', '9788423677689', 'Rojo', 2367768, 'Disponible'),
('Mandela', 'Alain Blondel', '9788492197781', 'Rojo', 9219778, 'En préstamo'),
('Ecoeducación', 'Mario Gomboli', '9788421632871', 'Rojo', 2163287, 'Disponible'),
('Cuentos de Todos los Colores', 'Aro Sáinz de la Maza', '9788478711239', 'Rojo', 7871123, 'Disponible'),--ID5

('Tristeza', 'Ana Serna Vara', '9788467774221', 'Rosa', 6777422, 'Disponible'),
('Miedo', 'Ana Serna Vara', '9788467774269', 'Rosa', 6777426, 'Disponible'),
('Alegría', 'Ana Serna Vara', '9788467774222', 'Rosa', 6777423, 'Disponible'),
('Enfado', 'Ana Serna Vara', '9788467774252', 'Rosa', 6777425, 'Disponible'),
('Sinceridad', 'Violeta Monreal', '9788439208907', 'Rosa', 3920890, 'Disponible'),--ID10

('Las chicas son guerreras', 'Irene Cívico y Sergio Parra', '9788490436547', 'Morado', 9043654, 'En préstamo'),
('Inventoras y sus inventos', 'Aitziber López-Lozano', '9788494743238', 'Morado', 9474323, 'Disponible'),
('Luchadoras', 'Cristina Serret Alonso', '9788413610115', 'Morado', 1361011, 'Disponible'),
('No me cuentes cuentos', 'Varios Autores', '9788417922290', 'Morado', 1792229, 'Disponible'),
('Mujeres exploradoras', 'Riccardo Francaviglia', '9788468269719', 'Morado', 6826971, 'Disponible'),--ID15

('Students in space', 'Craig Wright', '9780194400992', 'Amarillo', 1944009, 'Disponible'),
('Best friends in Fairyland', 'Daisy Meadows', '9780545222938', 'Amarillo', 5452229, 'Disponible'),
('Monster Party', 'Parragon Books', '9781472310187', 'Amarillo', 1472310, 'Disponible'),
('The Birthday Cake', 'Alex Lane', '9780198300922', 'Amarillo', 1983009, 'Disponible'),
('Billy the Kid', 'Ruth Miskin y Gill Munton', '9780198386797', 'Amarillo', 1983867, 'Disponible'),--ID20

('¡Qué Cosas!', 'Edith Schreiber-Wicke', '9788434836778', 'Marron', 3483677, 'Disponible'),
('¡Una de Piratas!', 'José Luis Alonso de Santos', '9788434870628', 'Marron', 3487062, 'Disponible'),
('4 años, 6 meses y 3 días después', 'Emmanuel Bourdier', '9788426366948', 'Marron', 2636694, 'Disponible'),
('A vueltas con mi nombre', 'Alice Vieira', '9788434830905', 'Marron', 3483090, 'Disponible'),
('Abdel', 'Enrique Páez', '9788467577853', 'Marron', 6757785, 'Disponible'),--ID25

('Madera ¡Desechos!', 'Veronica Bonar', '9788426326362', 'Blanco', 2632636, 'Disponible'),
('Asterix, El Galo', 'René Goscinny', '9788466648076', 'Blanco', 6664807, 'En préstamo'),
('Belfy y Lillibit 4', 'Pepe Gálvez', '9788466650147', 'Blanco', 6665014, 'Disponible'),
('Belfy y Lillibit 6', 'Pepe Gálvez', '9788466650178', 'Blanco', 6665017, 'Disponible'),
('Breve Historia de Aragón', 'José Antonio Parrilla', '9788450097597', 'Blanco', 5009759, 'Disponible'),--ID30

('Musicando con... Rossini', 'Montse Sanuy', '9788430545841', 'Negro', 3054584, 'Disponible'),
('Musicando con... Beethoven', 'Montse Sanuy', '9788430545827', 'Negro', 3054582, 'Disponible'),
('Musicando con... Chopin', 'Montse Sanuy', '9788430566877', 'Negro', 3056687, 'Disponible'),
('Musicando con... Verdi', 'Montse Sanuy', '9788430561353', 'Negro', 3056135, 'Disponible'),
('Musicando con... Strauss', 'Montse Sanuy', '9788430566860', 'Negro', 3056686, 'Disponible'),--ID35

('¡Buenos Días!', 'Asunción Lissón', '9788424606596', 'Verde', 2460659, 'Disponible'),
('¡Caramba con los amigos!', 'Ricardo Alcántara', '9788478644742', 'Verde', 7864474, 'Disponible'),
('¡Cómo brilla el mar!', 'Mercè Company González', '9788434836631', 'Verde', 3483663, 'Disponible'),
('¡Crea!', 'Román Belmonte Andújar', '9788494808579', 'Verde', 9480857, 'Disponible'),
('¡Cuánto me quieren!', 'Alejandra Vallejo-Nágera', '9788420464640', 'Verde', 2046464, 'Disponible'),--ID40

('¡¡¡PAPÁÁÁ!!!', 'Carles Cano', '9788469885611', 'Naranja', 6988561, 'Disponible'),
('¡Cómo molo!', 'Elvira Lindo', '9788420458564', 'Naranja', 2045856, 'En préstamo'),
('¡Corre, Sebastián, Corre!', 'Juan Kruz Igerabide', '9788467221664', 'Naranja', 6722166, 'Disponible'),
('¡Cumpleaños feliz!', 'Carmen Vázquez-Vigo', '9788421620816', 'Naranja', 2162081, 'Disponible'),
('¡Encerrados en clase!', 'Miquel Capó', '9788418318917', 'Naranja', 1831891, 'Disponible'),--ID45

('Descubrir el mundo: La Selva', 'Varios Autores', '9788482986128', 'Azul', 8298612, 'Disponible'),
('El Sistema Solar', 'Gaby Goldsack', '9788467533941', 'Azul', 6753394, 'En préstamo'),
('La capa de Ozono', 'Tony Hare', '9788434832602', 'Azul', 3483260, 'Disponible'),
('Animales desaparecidos', 'Claude Delafosse', '9788467552201', 'Azul', 6755220, 'Disponible'),
('Salvemos la Tierra', 'Jonathon Porritt', '9788422637462', 'Azul', 2263746, 'Disponible');--ID50

-- 4. Tabla de Alumnado
CREATE TABLE IF NOT EXISTS Alumnado (
    id_alumnado INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    apellidos TEXT NOT NULL,
    clase TEXT,
    edad INTEGER,
    codigo_de_carnet TEXT UNIQUE,
    estado_de_sancion TEXT
);

INSERT INTO Alumnado (nombre, apellidos, clase, edad, codigo_de_carnet, estado_de_sancion) VALUES 
('Hugo', 'Martínez Soler', 'Infantil', 5, '26-INF-HUMA', 'Ninguna'), 
('Sara', 'León Vega', '1º Primaria', 6, '26-1PRI-SALE', 'Retraso en devolución'), 
('Daniel', 'Navarro Paz', '2º Primaria', 7, '26-2PRI-DANA', 'Ninguna'), 
('Irene', 'Castillo Mora', '3º Primaria', 8, '26-3PRI-IRCA', 'Ninguna'), 
('Alejandro', 'Gómez Ruiz', '4º Primaria', 9, '26-4PRI-ALGO', 'Libro dañado'), 
('Paula', 'Jiménez Saez', '5º Primaria', 10, '26-5PRI-PAJI', 'Ninguna'), 
('Adrián', 'Molina Vicario', '6º Primaria', 11, '26-6PRI-ADMO', 'Ninguna'), 
('Beatriz', 'Díaz Ferrero', '3º Primaria', 8, '26-3PRI-BEDI', 'Ninguna'), 
('Mario', 'Blanco Hernández', '4º Primaria', 9, '26-4PRI-MABL', 'Ninguna'), 
('Natalia', 'Crespo Tovar', '6º Primaria', 11, '26-6PRI-NACR', 'Ninguna');

-- 5. Tabla de Préstamos
CREATE TABLE IF NOT EXISTS Prestamo (
    id_prestamo INTEGER PRIMARY KEY AUTOINCREMENT,
    id_alumnado INTEGER,
    id_libro INTEGER,
    id_usuario INTEGER,
    fecha_de_salida DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_de_devolucion DATE,
    estado_del_prestamo TEXT DEFAULT 'Activo',
    FOREIGN KEY (id_alumnado) REFERENCES Alumnado(id_alumnado),
    FOREIGN KEY (id_libro) REFERENCES Libro(id_libro),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

-- Préstamos 2026
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(1, 42, 5, '2026-01-20', '2026-02-03', 'Activo'),
(1, 15, 6, '2026-01-02', '2026-01-09', 'Devuelto'),-- Hugo Martínez (Alumno 1) devolvió otro libro a principios de mes
(1, 1, 5, '2026-01-05', '2026-01-15', 'Devuelto'),-- Hugo Martínez (Alumno 1) ya devolvió un libro anteriormente
(2, 6, 5, '2026-01-25', '2026-02-08', 'Activo'), --Préstamo Activo: A tiempo (Devolución futura)
(3, 22, 7, '2026-01-10', '2026-01-24', 'Devuelto'),-- Daniel Navarro (Alumno 3) devolvió su préstamo a tiempo
(4, 9, 5, '2026-01-29', '2026-02-12', 'Activo'),
(4, 11, 6, '2026-01-21', '2026-02-04', 'Activo'),
(5, 12, 6, '2026-01-01', '2026-01-15', 'Activo'), --Préstamo Activo: RETRASADO (Fecha de devolución ya pasó)
(6, 18, 7, '2026-01-10', '2026-01-24', 'Devuelto'), --Préstamo Finalizado: Devuelto correctamente
(7, 48, 8, '2026-01-12', '2026-01-26', 'Devuelto'),
(7, 47, 1, '2026-01-22', '2026-02-05', 'Activo'),
(8, 23, 6, '2026-01-18', '2026-02-01', 'Activo'),
(9, 39, 7, '2025-12-15', '2025-12-29', 'Activo'), --Préstamo Activo: RETRASADO (Muy antiguo)
(10, 50, 5, '2026-01-05', '2026-01-19', 'Devuelto'), --Finalizado: Historial de Natalia Crespo
(NULL, 3, 5, '2026-01-27', '2026-02-10', 'Activo'),
(NULL, 31, 8, '2026-01-28', '2026-02-11', 'Activo'),
(NULL, 10, 5, '2026-01-07', '2026-01-14', 'Devuelto'),--Elena Cano (Profesora/Usuario 5) ya devolvió un libro
(NULL, 27, 6, '2026-01-27', '2026-02-10', 'Activo'),
(NULL, 35, 6, '2026-01-02', '2026-01-16', 'Devuelto');--Javier López (Profesor/Usuario 6) devolvió un libro

-- 12 Préstamos ya FINALIZADOS (Devueltos)
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(2, 7, 5, '2026-01-05', '2026-01-12', 'Devuelto'),   -- Sara (1º Pri) devolvió Miedo
(3, 16, 7, '2026-01-08', '2026-01-15', 'Devuelto'),  -- Daniel (2º Pri) devolvió Students in space
(5, 21, 6, '2026-01-10', '2026-01-17', 'Devuelto'),  -- Alejandro (4º Pri) devolvió ¡Qué Cosas!
(8, 2, 8, '2026-01-12', '2026-01-19', 'Devuelto'),   -- Beatriz (3º Pri) devolvió Niños y Niñas del Mundo
(10, 41, 5, '2026-01-15', '2026-01-22', 'Devuelto'), -- Natalia (6º Pri) devolvió ¡¡¡PAPÁÁÁ!!!
(4, 32, 6, '2026-01-18', '2026-01-25', 'Devuelto'),  -- Irene (3º Pri) devolvió Beethoven
(NULL, 13, 5, '2026-01-05', '2026-01-12', 'Devuelto'),-- Elena (Prof) devolvió Luchadoras
(NULL, 26, 6, '2026-01-07', '2026-01-14', 'Devuelto'),-- Javier (Prof) devolvió Madera ¡Desechos!
(6, 40, 7, '2026-01-20', '2026-01-27', 'Devuelto'),  -- Paula (5º Pri) devolvió ¡Cuánto me quieren!
(9, 33, 8, '2026-01-22', '2026-01-29', 'Devuelto'),  -- Mario (4º Pri) devolvió Chopin
(1, 4, 5, '2026-01-24', '2026-01-31', 'Devuelto'),   -- Hugo (Inf) devolvió Ecoeducación
(7, 49, 6, '2026-01-25', '2026-02-01', 'Devuelto');  -- Adrián (6º Pri) devolvió Animales desaparecidos

-- Libro: ¡Crea! (ISBN 9788494808579) - Varias solicitudes adicionales
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(4, 39, 5, '2025-11-01', '2025-11-15', 'Devuelto'), -- Irene lo leyó en noviembre
(1, 39, 6, '2025-10-05', '2025-10-19', 'Devuelto'), -- Hugo lo leyó en octubre
(NULL, 38, 5, '2025-09-10', '2025-09-24', 'Devuelto'); -- Elena (profesora) leyó el otro ejemplar

-- Libro: ¡Cómo molo! (ISBN 9788420458564) - Gran éxito entre alumnos
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(2, 42, 7, '2025-11-20', '2025-12-04', 'Devuelto'),
(6, 42, 8, '2025-12-05', '2025-12-19', 'Devuelto'),
(9, 42, 5, '2026-01-02', '2026-01-16', 'Devuelto'),
(10, 42, 6, '2025-10-15', '2025-10-29', 'Devuelto');

-- Libro: El Sistema Solar (ISBN 9788467533941)
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(3, 47, 5, '2025-12-01', '2025-12-15', 'Devuelto'),
(5, 47, 7, '2025-11-10', '2025-11-24', 'Devuelto'),
(NULL, 47, 6, '2025-10-20', '2025-11-03', 'Devuelto');

-- Libro: Mandela (ISBN 9788492197781)
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(7, 3, 8, '2025-11-05', '2025-11-19', 'Devuelto'),
(8, 3, 5, '2025-12-10', '2025-12-24', 'Devuelto');

-- Libro: Las chicas son guerreras (ISBN 9788490436547)
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(2, 11, 6, '2025-09-15', '2025-09-29', 'Devuelto'),
(4, 11, 7, '2025-10-01', '2025-10-15', 'Devuelto');

-- 3 Préstamos NUEVOS en CURSO (Activos)
INSERT INTO Prestamo (id_alumnado, id_libro, id_usuario, fecha_de_salida, fecha_de_devolucion, estado_del_prestamo) VALUES 
(5, 14, 7, '2026-02-01', '2026-02-15', 'Activo'),    -- Alejandro (4º Pri) tiene No me cuentes cuentos
(3, 38, 8, '2026-02-03', '2026-02-17', 'Activo'),    -- Daniel (2º Pri) tiene ¡Crea!
(NULL, 46, 5, '2026-02-05', '2026-02-19', 'Activo'); -- Elena (Prof) tiene Descubrir el mundo: La Selva