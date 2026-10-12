# SAP Customer Import Instructions

## How to Import SAP Customers

1. **Access the Import Page:**
   - Go to: `http://localhost/nextgen/admin/clients/import_sap`

2. **Prepare Your Excel File:**
   - Create an Excel file (.xlsx or .xls) with the following columns in Row 1:
   
   | Column | Field Name | Required | Maps To |
   |--------|------------|----------|---------|
   | A | Customer | Yes | VAT Number |
   | B | Sales Org | No | - |
   | C | Dist Channel | No | - |
   | D | Division | No | - |
   | E | Sales Office | No | - |
   | F | Name 1 | Yes | Company Name |
   | G | City | No | City |
   | H | Search Term | No | - |
   | I | Name 2 | No | - |
   | J | Street | No | Address |
   | K | House Number | No | - |
   | L | Postal Code | No | ZIP Code |
   | M | Country | No | Country (ISO2 code: CY, AE, AF) |
   | N | Region | No | State |
   | O | Telephone | No | Phone Number |
   | P | Email | No | Contact Email |
   | Q | Account Group | No | - |

3. **Sample Data:**
   ```
   1001045 | AE07 | A0 | 00 | AE07 | RCT INTERNATIONAL LIMITED | CYPRUS | RCT | RCT International Limited | | CYPRUS | | 0000 | CY | | | | E001
   1002018 | AE07 | A0 | 00 | AE07 | JK INTERNATIONAL FZE | DUBAI | J.K INTERN | JK INTERNATIONAL FZE | | | | 261137 | AE | | | | E002
   ```

4. **Upload and Import:**
   - Select your Excel file
   - Click "Import" button
   - System will import customers into `tblclients` table

## Field Mapping

- **Customer Code** → Stored in VAT field
- **Name 1** → Company Name
- **City** → City
- **Street** → Address
- **Postal Code** → ZIP
- **Country** → Country (converted from ISO2 code)
- **Region** → State
- **Telephone** → Phone Number
- **Email** → Primary Contact Email

## Notes

- Rows with empty Customer Code or Name 1 will be skipped
- Each customer will automatically get a primary contact created
- If email is provided, it will be assigned to the primary contact
