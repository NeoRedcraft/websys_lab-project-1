# Contributors:
* Felipe M. Panugan III - NeoRedcraft
* Edelle Gibben M. Lumabi - edelle-del
* Aliyana Trisha Harun - ataharun05
* Craig Zyrus Manuel - craigmanuel
* Earth Luis Diño - GrudgeTale
* Sylar Zvi Villacruz - SYZVAL

## HOW TO RUN

### Option 1: Using Docker (Recommended)
1. Clone the repository:
   ```bash
   git clone https://github.com/NeoRedcraft/websys_lab-project-1.git
   cd websys_lab-project-1
   ```

2. Create `.env` file from example:
   ```bash
   cp .env.example .env
   ```

3. Update `.env` with your Supabase credentials

4. Run with Docker Compose:
   ```bash
   docker-compose up
   ```

5. Access the application at `http://localhost:8000`

### Option 2: Using PHP Built-in Server
1. Clone the repository:
   ```bash
   git clone https://github.com/NeoRedcraft/websys_lab-project-1.git
   cd websys_lab-project-1
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create `.env` file from example and update with your credentials

4. Run the server:
   ```bash
   php -S localhost:8000
   ```              
