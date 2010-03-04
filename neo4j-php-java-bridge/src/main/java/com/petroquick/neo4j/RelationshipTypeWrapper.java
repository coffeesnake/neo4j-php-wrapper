package com.petroquick.neo4j;

import org.neo4j.graphdb.RelationshipType;

import java.util.HashMap;
import java.util.Map;

/**
 * This class is not needed. DynamicRelationshipType could be used instead.
 *
 * @author Philip Rud
 */
public class RelationshipTypeWrapper implements RelationshipType {
    private String name;

    private static Map<String, RelationshipTypeWrapper> relashonshipTypes = new HashMap<String, RelationshipTypeWrapper>();

    public String name() {
        return name;
    }

    private RelationshipTypeWrapper(String name) {
        this.name = name;
    }

    public static synchronized RelationshipTypeWrapper get(String name) {
        if (!relashonshipTypes.containsKey(name))
            relashonshipTypes.put(name, new RelationshipTypeWrapper(name));

        return relashonshipTypes.get(name);
    }
}