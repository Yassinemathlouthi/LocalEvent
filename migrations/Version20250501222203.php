<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501222203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP SEQUENCE rsvp_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE comment_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP CONSTRAINT comment_user_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP CONSTRAINT comment_event_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_category DROP CONSTRAINT event_category_event_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_category DROP CONSTRAINT event_category_category_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attendance DROP CONSTRAINT rsvp_user_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attendance DROP CONSTRAINT rsvp_event_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE comment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE attendance
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user DROP name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user DROP bio
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user DROP location
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user DROP profile_picture
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user DROP interests
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER id DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER email TYPE VARCHAR(180)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER roles TYPE JSON USING roles::json
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER roles DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER roles SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX app_user_email_key RENAME TO UNIQ_88BDF3E9E7927C74
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX category_name_key
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP description
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ALTER id DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP CONSTRAINT event_created_by_fkey
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP CONSTRAINT event_category_id_fkey
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3BAE0AA7DE12AB56
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3BAE0AA712469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP created_by
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP category_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP image
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP organizer_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP updated_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP event_date
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER id DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER created_at DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER is_approved DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event RENAME COLUMN category_name TO category
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE rsvp_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE comment_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE comment (id SERIAL NOT NULL, user_id INT DEFAULT NULL, event_id INT DEFAULT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526C71F7E88B ON comment (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_category (event_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(event_id, category_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_40A0F01171F7E88B ON event_category (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_40A0F01112469DE2 ON event_category (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE attendance (id SERIAL NOT NULL, user_id INT DEFAULT NULL, event_id INT DEFAULT NULL, status VARCHAR(20) NOT NULL, responded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP, joined_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6DE30D91A76ED395 ON attendance (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6DE30D9171F7E88B ON attendance (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT comment_user_id_fkey FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT comment_event_id_fkey FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_category ADD CONSTRAINT event_category_event_id_fkey FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_category ADD CONSTRAINT event_category_category_id_fkey FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attendance ADD CONSTRAINT rsvp_user_id_fkey FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attendance ADD CONSTRAINT rsvp_event_id_fkey FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ADD name VARCHAR(100) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ADD bio TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ADD location VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ADD profile_picture TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ADD interests TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE app_user_id_seq
        SQL);
        $this->addSql(<<<'SQL'
            SELECT setval('app_user_id_seq', (SELECT MAX(id) FROM app_user))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER id SET DEFAULT nextval('app_user_id_seq')
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER email TYPE VARCHAR(100)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER roles TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER roles SET DEFAULT 'ROLE_USER'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE app_user ALTER roles DROP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX uniq_88bdf3e9e7927c74 RENAME TO app_user_email_key
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD description TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE category_id_seq
        SQL);
        $this->addSql(<<<'SQL'
            SELECT setval('category_id_seq', (SELECT MAX(id) FROM category))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ALTER id SET DEFAULT nextval('category_id_seq')
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX category_name_key ON category (name)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD created_by INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD category_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD image TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD organizer_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD event_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE event_id_seq
        SQL);
        $this->addSql(<<<'SQL'
            SELECT setval('event_id_seq', (SELECT MAX(id) FROM event))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER id SET DEFAULT nextval('event_id_seq')
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER created_at SET DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ALTER is_approved SET DEFAULT false
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event RENAME COLUMN category TO category_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT event_created_by_fkey FOREIGN KEY (created_by) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT event_category_id_fkey FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3BAE0AA7DE12AB56 ON event (created_by)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3BAE0AA712469DE2 ON event (category_id)
        SQL);
    }
}
