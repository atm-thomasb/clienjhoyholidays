-- ========================================================================
-- Copyright (C) 2016 Frederic France  <frederic.france@free.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ========================================================================

CREATE TABLE llx_c_enjoyholidays_transport_mode
(
    rowid		integer AUTO_INCREMENT PRIMARY KEY,
    entity		integer DEFAULT 1,
    code		varchar(32)				NOT NULL,
    label		varchar(128)			NOT NULL,
    active		integer DEFAULT 1,
    use_default	integer DEFAULT 1,
    description	varchar(255)
)ENGINE=innodb;
